<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Models\ContentSubmission;
use App\Models\SubmissionCategory;
use App\Models\Payment;
use App\Models\Notification;

// ✅ FIXED: Menghapus import PaymentController yang menyebabkan konflik
// Jangan import PaymentController di sini karena menyebabkan class redeclaration error

class ContentSubmissionController extends Controller
{
    /**
     * Display user's submission dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Check if user can create submissions
        if (!$user->canCreateSubmissions()) {
            return redirect()->route('home')
                ->with('error', 'Akun Anda harus diverifikasi terlebih dahulu untuk dapat membuat submission.');
        }
        
        // Get filters
        $status = $request->get('status');
        $category = $request->get('category');
        $search = $request->get('search');
        
        // Build query
        $query = ContentSubmission::where('user_id', $user->id)
                                 ->with(['category', 'payment', 'approvedBy']);
        
        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($category) {
            $query->where('category_id', $category);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        $submissions = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Get user statistics
        $stats = ContentSubmission::getUserSubmissionStats($user->id);
        
        // Get active categories
        $categories = SubmissionCategory::getActiveCategories();
        
        return view('submissions.index', compact('submissions', 'stats', 'categories', 'status', 'category', 'search'));
    }

    /**
     * Show the form for creating a new submission
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Check if user can create submissions
        if (!$user->canCreateSubmissions()) {
            return redirect()->route('submissions.index')
                ->with('error', 'Akun Anda harus diverifikasi terlebih dahulu untuk dapat membuat submission.');
        }
        
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
            
            // Check if user can create submissions
            if (!$user->canCreateSubmissions()) {
                return back()->with('error', 'Akun Anda harus diverifikasi terlebih dahulu untuk dapat membuat submission.');
            }
            
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
            
            // Check if category is active and can accept new submissions
            if (!$category->canAcceptNewSubmissions()) {
                return back()->with('error', 'Kategori ini sedang tidak menerima submission baru.');
            }
            
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
        // Check ownership or moderator privileges
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

            // Check if can be deleted
            if (!$submission->canBeDeleted()) {
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

    // ===== ADMIN METHODS =====

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

        // ✅ FIXED: Ubah default filter dari 'pending_approval' ke 'all' agar menampilkan semua data
        $status = $request->get('status', 'all'); // Dulu: 'pending_approval' -> Sekarang: 'all'
        $category = $request->get('category');
        $search = $request->get('search');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        // Build query
        $query = ContentSubmission::with(['user', 'category', 'payment']);
        
        // ✅ FIXED: Only apply status filter if not 'all'
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($category) {
            $query->where('category_id', $category);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('full_name', 'LIKE', "%{$search}%")
                               ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        // Apply sorting
        $allowedSorts = ['created_at', 'title', 'status', 'submitted_at', 'approved_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        $submissions = $query->paginate(15);
        
        // Get statistics
        $stats = ContentSubmission::getAdminStats();
        
        // ✅ FIXED: Menghilangkan dependency ke PaymentController
        $paymentStats = [];
        try {
            if (class_exists('\App\Models\Payment')) {
                $paymentStats = Payment::getAdminStats();
            }
        } catch (\Exception $e) {
            Log::warning('Could not get payment stats', ['error' => $e->getMessage()]);
        }
        
        // Get categories for filter
        $categories = SubmissionCategory::getActiveCategories();
        
        return view('admin.submissions.index', compact(
            'submissions', 
            'stats', 
            'paymentStats', 
            'categories',
            'status',
            'category',
            'search',
            'sort',
            'direction'
        ));
    }

    /**
     * ✅ ENHANCED: Approve submission - support JSON response
     */
    public function approve(ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        if (!$submission->canBeApproved()) {
            $message = 'Submission tidak dapat disetujui pada status saat ini.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        try {
            $submission->approve($user->id);

            Log::info('Submission approved', [
                'submission_id' => $submission->id,
                'approved_by' => $user->id,
                'title' => $submission->title
            ]);

            // Clear relevant cache
            Cache::forget('admin_integrated_stats_' . $user->id);
            Cache::forget('admin_pending_submissions_' . $user->id);

            $message = 'Submission berhasil disetujui!';
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => $message,
                    'submission' => [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'approved_at' => $submission->updated_at
                    ]
                ]);
            }
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error approving submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);

            $message = 'Terjadi kesalahan saat menyetujui submission.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * Reject submission
     */
    public function reject(Request $request, ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500']
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        if (!$submission->canBeRejected()) {
            $message = 'Konten tidak dapat ditolak pada status saat ini.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        try {
            $submission->reject($request->rejection_reason, $user->id);

            Log::info('Submission rejected', [
                'submission_id' => $submission->id,
                'rejected_by' => $user->id,
                'reason' => $request->rejection_reason
            ]);

            // Clear relevant cache
            Cache::forget('admin_integrated_stats_' . $user->id);
            Cache::forget('admin_pending_submissions_' . $user->id);

            $message = 'Konten telah ditolak.';
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => $message,
                    'submission' => [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'rejection_reason' => $submission->rejection_reason
                    ]
                ]);
            }
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error rejecting submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);

            $message = 'Terjadi kesalahan saat menolak submission.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * ✅ ENHANCED: Publish submission - support JSON response
     */
    public function publish(ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        if (!$submission->canBePublished()) {
            $message = 'Submission tidak dapat dipublikasi pada status saat ini.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        try {
            $result = $submission->publishToGroup();
            
            if ($result) {
                // Update submission status
                $submission->update([
                    'status' => 'published',
                    'published_by' => $user->id,
                    'published_at' => now()
                ]);

                Log::info('Submission published', [
                    'submission_id' => $submission->id,
                    'published_by' => $user->id,
                    'title' => $submission->title
                ]);

                // Clear relevant cache
                Cache::forget('admin_integrated_stats_' . $user->id);
                Cache::forget('admin_pending_submissions_' . $user->id);
                
                // Increment today's approved count for stats
                $todayKey = 'admin_total_approved_today';
                $todayCount = Cache::get($todayKey, 0);
                Cache::put($todayKey, $todayCount + 1, now()->endOfDay());

                // Create notification for user
                if (class_exists('\App\Models\Notification')) {
                    try {
                        Notification::createSubmissionNotification($submission->id, 'submission_published');
                    } catch (\Exception $e) {
                        Log::warning('Failed to create submission publication notification', [
                            'submission_id' => $submission->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $message = 'Submission berhasil dipublikasi ke komunitas!';
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true, 
                        'message' => $message,
                        'submission' => [
                            'id' => $submission->id,
                            'status' => $submission->status,
                            'published_at' => $submission->updated_at
                        ]
                    ]);
                }
                return back()->with('success', $message);
            } else {
                $message = 'Gagal mempublikasikan konten ke komunitas.';
                if (request()->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message]);
                }
                return back()->with('error', $message);
            }

        } catch (\Exception $e) {
            Log::error('Error publishing submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);

            $message = 'Terjadi kesalahan saat mempublikasi submission.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    // ===== FILE HANDLING METHODS =====

    /**
     * Handle file upload
     */
    private function handleFileUpload($file, $folder = 'submissions')
    {
        try {
            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

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
     * ✅ ENHANCED: Categorize file types with better detection
     */
    private function getFileTypeCategory($mimeType)
    {
        // Image types
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        // PDF
        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }
        
        // Document types
        $documentTypes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.oasis.opendocument.text'
        ];
        if (in_array($mimeType, $documentTypes)) {
            return 'document';
        }
        
        // Spreadsheet types
        $spreadsheetTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet'
        ];
        if (in_array($mimeType, $spreadsheetTypes)) {
            return 'spreadsheet';
        }
        
        // Presentation types
        $presentationTypes = [
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ];
        if (in_array($mimeType, $presentationTypes)) {
            return 'presentation';
        }
        
        // Archive types
        $archiveTypes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed'
        ];
        if (in_array($mimeType, $archiveTypes)) {
            return 'archive';
        }
        
        return 'file';
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

        if (!Storage::disk('public')->exists($submission->attachment_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download($submission->attachment_path, $submission->attachment_name);
    }

    // ===== API METHODS =====

    /**
     * Get submission statistics for API
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        
        if ($user->hasModeratorPrivileges()) {
            $stats = ContentSubmission::getAdminStats();
            
            // ✅ FIXED: Menghilangkan dependency ke PaymentController
            $paymentStats = [];
            try {
                if (class_exists('\App\Models\Payment')) {
                    $paymentStats = Payment::getAdminStats();
                }
            } catch (\Exception $e) {
                Log::warning('Could not get payment stats for API', ['error' => $e->getMessage()]);
            }
            
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