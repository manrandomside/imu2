<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
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

        // Get filters
        $status = $request->get('status', 'pending_approval');
        $category = $request->get('category');
        $search = $request->get('search');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        // Build query
        $query = ContentSubmission::with(['user', 'category', 'payment']);
        
        // Apply filters
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
        $paymentStats = Payment::getAdminStats();
        
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

        if (!$submission->canBeRejected()) {
            return back()->with('error', 'Konten tidak dapat ditolak pada status saat ini.');
        }

        $submission->reject($request->rejection_reason, $user->id);

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
     * ✅ ADDED: Bulk approve submissions
     */
    public function bulkApprove(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'submission_ids' => ['required', 'array'],
            'submission_ids.*' => ['exists:content_submissions,id'],
            'publish' => ['boolean']
        ]);

        $approved = 0;
        $errors = [];
        $shouldPublish = $request->get('publish', false);

        foreach ($request->submission_ids as $submissionId) {
            $submission = ContentSubmission::find($submissionId);
            
            if (!$submission) {
                $errors[] = "Submission ID {$submissionId} not found.";
                continue;
            }
            
            if (!$submission->canBeApproved()) {
                $errors[] = "Submission '{$submission->title}' cannot be approved.";
                continue;
            }
            
            try {
                $submission->approve($user->id, $shouldPublish);
                $approved++;
            } catch (\Exception $e) {
                $errors[] = "Failed to approve '{$submission->title}': " . $e->getMessage();
            }
        }

        $message = "Berhasil menyetujui {$approved} konten.";
        if ($shouldPublish) {
            $message .= " Konten telah dipublikasikan.";
        }
        
        if (!empty($errors)) {
            $message .= " Terdapat " . count($errors) . " error.";
        }

        return back()->with('success', $message);
    }

    /**
     * ✅ ADDED: Bulk reject submissions
     */
    public function bulkReject(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'submission_ids' => ['required', 'array'],
            'submission_ids.*' => ['exists:content_submissions,id'],
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500']
        ]);

        $rejected = 0;
        $errors = [];

        foreach ($request->submission_ids as $submissionId) {
            $submission = ContentSubmission::find($submissionId);
            
            if (!$submission) {
                $errors[] = "Submission ID {$submissionId} not found.";
                continue;
            }
            
            if (!$submission->canBeRejected()) {
                $errors[] = "Submission '{$submission->title}' cannot be rejected.";
                continue;
            }
            
            try {
                $submission->reject($request->rejection_reason, $user->id);
                $rejected++;
            } catch (\Exception $e) {
                $errors[] = "Failed to reject '{$submission->title}': " . $e->getMessage();
            }
        }

        $message = "Berhasil menolak {$rejected} konten.";
        
        if (!empty($errors)) {
            $message .= " Terdapat " . count($errors) . " error.";
        }

        return back()->with('success', $message);
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

    /**
     * ✅ ADDED: Get submission trends for dashboard
     */
    public function getTrends(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $days = $request->get('days', 30);
        $trends = ContentSubmission::getSubmissionTrends($days);
        
        return response()->json(['trends' => $trends]);
    }

    /**
     * ✅ ADDED: Search submissions API
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'status' => ['nullable', 'in:pending_payment,pending_approval,approved,rejected,published'],
            'category_id' => ['nullable', 'exists:submission_categories,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50']
        ]);

        $user = Auth::user();
        $query = ContentSubmission::search($request->q)
                                  ->with(['category', 'user', 'payment']);

        // If not admin/moderator, only show user's submissions
        if (!$user->hasModeratorPrivileges()) {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $limit = $request->get('limit', 10);
        $submissions = $query->limit($limit)->get();

        return response()->json([
            'submissions' => $submissions,
            'count' => $submissions->count()
        ]);
    }

    /**
     * ✅ ADDED: Export submissions
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'format' => ['required', 'in:csv,json'],
            'status' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:submission_categories,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date']
        ]);

        $query = ContentSubmission::with(['user', 'category', 'payment', 'approvedBy']);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        if ($request->format === 'csv') {
            return $this->exportToCsv($submissions);
        } else {
            return $this->exportToJson($submissions);
        }
    }

    /**
     * ✅ ADDED: Export to CSV
     */
    private function exportToCsv($submissions)
    {
        $filename = 'submissions_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($submissions) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Title', 'Category', 'User', 'Status', 'Amount', 
                'Created At', 'Submitted At', 'Approved At', 'Approved By'
            ]);

            // CSV data
            foreach ($submissions as $submission) {
                fputcsv($file, [
                    $submission->id,
                    $submission->title,
                    $submission->category->name ?? 'Unknown',
                    $submission->user->full_name ?? 'Unknown',
                    $submission->status_display,
                    $submission->category->formatted_price ?? 'Free',
                    $submission->created_at->format('Y-m-d H:i:s'),
                    $submission->submitted_at?->format('Y-m-d H:i:s') ?? '',
                    $submission->approved_at?->format('Y-m-d H:i:s') ?? '',
                    $submission->approvedBy->full_name ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * ✅ ADDED: Export to JSON
     */
    private function exportToJson($submissions)
    {
        $filename = 'submissions_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        $data = $submissions->map(function($submission) {
            return [
                'id' => $submission->id,
                'title' => $submission->title,
                'description' => $submission->description,
                'category' => [
                    'id' => $submission->category->id ?? null,
                    'name' => $submission->category->name ?? 'Unknown',
                    'price' => $submission->category->price ?? 0
                ],
                'user' => [
                    'id' => $submission->user->id ?? null,
                    'name' => $submission->user->full_name ?? 'Unknown',
                    'email' => $submission->user->email ?? 'Unknown'
                ],
                'status' => $submission->status,
                'status_display' => $submission->status_display,
                'has_attachment' => $submission->hasAttachment(),
                'payment' => $submission->payment ? [
                    'id' => $submission->payment->id,
                    'amount' => $submission->payment->amount,
                    'status' => $submission->payment->status,
                    'method' => $submission->payment->payment_method
                ] : null,
                'created_at' => $submission->created_at->toISOString(),
                'submitted_at' => $submission->submitted_at?->toISOString(),
                'approved_at' => $submission->approved_at?->toISOString(),
                'approved_by' => $submission->approvedBy->full_name ?? null
            ];
        });

        return response()->json(['submissions' => $data])
                        ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * ✅ ADDED: Get category statistics for admin
     */
    public function getCategoryStats()
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $categoryStats = SubmissionCategory::getActiveCategories()
                                          ->map(function($category) {
                                              return [
                                                  'id' => $category->id,
                                                  'name' => $category->name,
                                                  'submissions_count' => $category->submissions()->count(),
                                                  'pending_count' => $category->submissions()->pending()->count(),
                                                  'approved_count' => $category->submissions()->approved()->count(),
                                                  'total_revenue' => $category->total_revenue,
                                                  'approval_rate' => $category->getApprovalRate()
                                              ];
                                          });

        return response()->json(['category_stats' => $categoryStats]);
    }

    /**
     * ✅ ADDED: Validate submission before creating
     */
    public function validateSubmission(Request $request)
    {
        $request->validate([
            'category_id' => ['required', 'exists:submission_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:5000']
        ]);

        $category = SubmissionCategory::findOrFail($request->category_id);
        
        $validation = [
            'valid' => true,
            'errors' => [],
            'category' => [
                'name' => $category->name,
                'price' => $category->formatted_price,
                'allowed_file_types' => $category->allowed_file_types_string,
                'max_file_size' => $category->formatted_max_file_size
            ]
        ];

        // Check if category accepts new submissions
        if (!$category->canAcceptNewSubmissions()) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Kategori ini sedang tidak menerima submission baru.';
        }

        // Check user verification
        if (!Auth::user()->canCreateSubmissions()) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Akun Anda harus diverifikasi terlebih dahulu.';
        }

        return response()->json($validation);
    }
}