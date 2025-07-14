<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\ContentSubmission;
use App\Models\SubmissionCategory;
use App\Models\Payment;
use App\Models\Notification;

class ContentSubmissionController extends Controller
{
    /**
     * Display user's submission dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get user submissions with pagination
        $submissions = ContentSubmission::where('user_id', $user->id)
                                        ->with(['category', 'payment', 'approvedBy'])
                                        ->orderBy('created_at', 'desc')
                                        ->paginate(10);
        
        // Get user statistics
        $stats = ContentSubmission::getUserSubmissionStats($user->id);
        
        // Get active categories
        $categories = SubmissionCategory::getActiveCategories();
        
        return view('submissions.index', compact('submissions', 'stats', 'categories'));
    }

    /**
     * Show the form for creating a new submission
     */
    public function create(Request $request)
    {
        $categories = SubmissionCategory::getActiveCategories();
        $selectedCategoryId = $request->get('category');
        $selectedCategory = null;
        
        if ($selectedCategoryId) {
            $selectedCategory = SubmissionCategory::find($selectedCategoryId);
        }
        
        return view('submissions.create', compact('categories', 'selectedCategory'));
    }

    /**
     * Store a newly created submission
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Validation
            $request->validate([
                'category_id' => ['required', 'exists:submission_categories,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'min:50', 'max:5000'],
                'attachment' => ['nullable', 'file', 'max:10240'], // 10MB
            ], [
                'category_id.required' => 'Kategori wajib dipilih.',
                'category_id.exists' => 'Kategori tidak valid.',
                'title.required' => 'Judul wajib diisi.',
                'title.max' => 'Judul maksimal 255 karakter.',
                'description.required' => 'Deskripsi wajib diisi.',
                'description.min' => 'Deskripsi minimal 50 karakter.',
                'description.max' => 'Deskripsi maksimal 5000 karakter.',
                'attachment.file' => 'Lampiran harus berupa file.',
                'attachment.max' => 'Ukuran file maksimal 10 MB.',
            ]);

            $category = SubmissionCategory::findOrFail($request->category_id);
            
            // Handle file upload
            $attachmentData = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                
                // Validate file against category rules
                $fileErrors = $category->validateFile($file);
                if (!empty($fileErrors)) {
                    throw ValidationException::withMessages([
                        'attachment' => $fileErrors
                    ]);
                }
                
                $attachmentData = $this->handleFileUpload($file, 'submissions');
            }

            // Create submission
            $submission = ContentSubmission::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'title' => $request->title,
                'description' => $request->description,
                'attachment_path' => $attachmentData['path'] ?? null,
                'attachment_type' => $attachmentData['type'] ?? null,
                'attachment_name' => $attachmentData['name'] ?? null,
                'attachment_size' => $attachmentData['size'] ?? null,
                'status' => 'pending_payment',
            ]);

            Log::info('Content submission created', [
                'submission_id' => $submission->id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'title' => $submission->title
            ]);

            return redirect()
                ->route('submissions.show', $submission)
                ->with('success', 'Konten berhasil dibuat! Silakan lanjutkan pembayaran untuk mengirim ke moderator.');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating content submission', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat membuat konten. Silakan coba lagi.');
        }
    }

    /**
     * Display the specified submission
     */
    public function show(ContentSubmission $submission)
    {
        // Check ownership
        if ($submission->user_id !== Auth::id() && !Auth::user()->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $submission->load(['category', 'payment', 'approvedBy', 'user']);
        
        return view('submissions.show', compact('submission'));
    }

    /**
     * Show the form for editing submission (only for pending_payment or rejected)
     */
    public function edit(ContentSubmission $submission)
    {
        // Check ownership
        if ($submission->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        // Check if can be edited
        if (!$submission->canBeEdited()) {
            return redirect()
                ->route('submissions.show', $submission)
                ->with('error', 'Konten tidak dapat diedit pada status saat ini.');
        }

        $categories = SubmissionCategory::getActiveCategories();
        
        return view('submissions.edit', compact('submission', 'categories'));
    }

    /**
     * Update the specified submission
     */
    public function update(Request $request, ContentSubmission $submission)
    {
        try {
            // Check ownership
            if ($submission->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access');
            }

            // Check if can be edited
            if (!$submission->canBeEdited()) {
                return redirect()
                    ->route('submissions.show', $submission)
                    ->with('error', 'Konten tidak dapat diedit pada status saat ini.');
            }

            // Validation
            $request->validate([
                'category_id' => ['required', 'exists:submission_categories,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'min:50', 'max:5000'],
                'attachment' => ['nullable', 'file', 'max:10240'],
                'remove_attachment' => ['nullable', 'boolean'],
            ]);

            $category = SubmissionCategory::findOrFail($request->category_id);

            // Handle attachment removal
            if ($request->remove_attachment) {
                $submission->deleteAttachment();
            }

            // Handle new file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                
                // Validate file against category rules
                $fileErrors = $category->validateFile($file);
                if (!empty($fileErrors)) {
                    throw ValidationException::withMessages([
                        'attachment' => $fileErrors
                    ]);
                }

                // Delete old attachment
                $submission->deleteAttachment();
                
                // Upload new attachment
                $attachmentData = $this->handleFileUpload($file, 'submissions');
                
                $submission->update([
                    'attachment_path' => $attachmentData['path'],
                    'attachment_type' => $attachmentData['type'],
                    'attachment_name' => $attachmentData['name'],
                    'attachment_size' => $attachmentData['size'],
                ]);
            }

            // Update submission
            $submission->update([
                'category_id' => $category->id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'pending_payment', // Reset to pending payment if rejected
            ]);

            return redirect()
                ->route('submissions.show', $submission)
                ->with('success', 'Konten berhasil diperbarui!');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating content submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui konten. Silakan coba lagi.');
        }
    }

    /**
     * Remove the specified submission
     */
    public function destroy(ContentSubmission $submission)
    {
        try {
            // Check ownership
            if ($submission->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access');
            }

            // Only allow deletion for certain statuses
            if (!in_array($submission->status, ['pending_payment', 'rejected'])) {
                return redirect()
                    ->route('submissions.show', $submission)
                    ->with('error', 'Konten tidak dapat dihapus pada status saat ini.');
            }

            // Delete attachment file
            $submission->deleteAttachment();

            // Delete associated payment if exists
            if ($submission->payment) {
                $submission->payment->deleteProof();
                $submission->payment->delete();
            }

            // Delete submission
            $submission->delete();

            Log::info('Content submission deleted', [
                'submission_id' => $submission->id,
                'user_id' => Auth::id()
            ]);

            return redirect()
                ->route('submissions.index')
                ->with('success', 'Konten berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Error deleting content submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus konten.');
        }
    }

    /**
     * Show admin approval dashboard
     */
    public function adminIndex(Request $request)
    {
        $user = Auth::user();
        
        // Check if user is admin or moderator
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        // Get filters
        $status = $request->get('status', 'pending_approval');
        $category = $request->get('category');
        
        // Build query
        $query = ContentSubmission::with(['user', 'category', 'payment']);
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($category) {
            $query->where('category_id', $category);
        }
        
        $submissions = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get statistics
        $stats = ContentSubmission::getAdminStats();
        $paymentStats = Payment::getAdminStats();
        
        // Get categories for filter
        $categories = SubmissionCategory::getActiveCategories();
        
        return view('admin.submissions.index', compact(
            'submissions', 
            'stats', 
            'paymentStats', 
            'categories',
            'status',
            'category'
        ));
    }

    /**
     * Approve submission
     */
    public function approve(Request $request, ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        if (!$submission->canBeApproved()) {
            return back()->with('error', 'Konten tidak dapat disetujui pada status saat ini.');
        }

        $shouldPublish = $request->get('publish', false);
        $submission->approve($user->id, $shouldPublish);

        $message = $shouldPublish ? 'Konten berhasil disetujui dan dipublikasikan!' : 'Konten berhasil disetujui!';
        
        return back()->with('success', $message);
    }

    /**
     * Reject submission
     */
    public function reject(Request $request, ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500']
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        if (!$submission->canBeApproved()) {
            return back()->with('error', 'Konten tidak dapat ditolak pada status saat ini.');
        }

        $submission->reject($user->id, $request->rejection_reason);

        return back()->with('success', 'Konten telah ditolak.');
    }

    /**
     * Publish approved submission
     */
    public function publish(ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        if (!$submission->canBePublished()) {
            return back()->with('error', 'Konten tidak dapat dipublikasikan pada status saat ini.');
        }

        $message = $submission->publishToGroup();
        
        if ($message) {
            return back()->with('success', 'Konten berhasil dipublikasikan ke komunitas!');
        } else {
            return back()->with('error', 'Gagal mempublikasikan konten ke komunitas.');
        }
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload($file, $folder = 'submissions')
    {
        try {
            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Store file
            $path = $file->storeAs($folder, $filename, 'public');

            // Determine file type category
            $mimeType = $file->getMimeType();
            $type = $this->getFileTypeCategory($mimeType);

            return [
                'path' => $path,
                'type' => $type,
                'name' => $originalName,
                'size' => $file->getSize(),
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);
            
            throw new \Exception('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Categorize file types
     */
    private function getFileTypeCategory($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif ($mimeType === 'application/pdf') {
            return 'pdf';
        } elseif (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
            return 'document';
        } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
            return 'spreadsheet';
        } else {
            return 'file';
        }
    }

    /**
     * Download attachment
     */
    public function downloadAttachment(ContentSubmission $submission)
    {
        // Check access permissions
        if ($submission->user_id !== Auth::id() && !Auth::user()->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        if (!$submission->hasAttachment()) {
            abort(404, 'File not found');
        }

        $filePath = storage_path('app/public/' . $submission->attachment_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $submission->attachment_name);
    }

    /**
     * Get submission statistics for API
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        
        if ($user->hasModeratorPrivileges()) {
            $stats = ContentSubmission::getAdminStats();
            $paymentStats = Payment::getAdminStats();
            
            return response()->json([
                'submission_stats' => $stats,
                'payment_stats' => $paymentStats,
            ]);
        } else {
            $stats = ContentSubmission::getUserSubmissionStats($user->id);
            
            return response()->json([
                'user_stats' => $stats,
            ]);
        }
    }
}