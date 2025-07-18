<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * ✅ Admin edit submission form
     */
    public function adminEdit(ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $submission->load(['category', 'payment', 'user']);
        $categories = SubmissionCategory::getActiveCategories();
        
        return view('admin.submissions.edit', compact('submission', 'categories'));
    }

    /**
     * ✅ Admin update submission
     */
    public function adminUpdate(Request $request, ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string|min:50|max:5000',
                'category_id' => 'required|exists:submission_categories,id',
                'status' => 'required|in:pending_payment,pending_approval,approved,published,rejected',
                'rejection_reason' => 'nullable|string|max:1000',
                'attachment' => 'nullable|file|max:10240', // 10MB max
            ], [
                'title.required' => 'Judul wajib diisi.',
                'description.required' => 'Deskripsi wajib diisi.',
                'description.min' => 'Deskripsi minimal 50 karakter.',
                'category_id.required' => 'Kategori wajib dipilih.',
                'status.required' => 'Status wajib dipilih.',
            ]);

            // Handle file upload if new file provided
            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'status' => $request->status,
            ];

            // Handle status-specific updates
            if ($request->status === 'approved' && $submission->status !== 'approved') {
                $updateData['approved_at'] = now();
                $updateData['approved_by'] = $user->id;
                $updateData['rejection_reason'] = null;
                $updateData['rejected_at'] = null;
                $updateData['rejected_by'] = null;
            }

            if ($request->status === 'rejected') {
                $updateData['rejection_reason'] = $request->rejection_reason;
                $updateData['rejected_at'] = now();
                $updateData['rejected_by'] = $user->id;
                $updateData['approved_at'] = null;
                $updateData['approved_by'] = null;
                $updateData['published_at'] = null;
                $updateData['published_by'] = null;
            }

            if ($request->status === 'published' && $submission->status !== 'published') {
                $updateData['published_at'] = now();
                $updateData['published_by'] = $user->id;
                
                // Publish to community if not already published
                if ($submission->status !== 'published') {
                    $submission->publishToGroup();
                }
            }

            // Handle new attachment upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment
                $submission->deleteAttachment();
                
                $file = $request->file('attachment');
                $attachmentData = $this->handleFileUpload($file, 'submissions');
                
                $updateData = array_merge($updateData, [
                    'attachment_path' => $attachmentData['path'],
                    'attachment_type' => $attachmentData['type'],
                    'attachment_name' => $attachmentData['name'],
                    'attachment_size' => $attachmentData['size'],
                ]);
            }

            $submission->update($updateData);

            // Clear relevant cache
            Cache::forget('admin_integrated_stats_' . $user->id);
            Cache::forget('submission_admin_stats');

            Log::info('Submission updated by admin', [
                'submission_id' => $submission->id,
                'admin_id' => $user->id,
                'old_status' => $submission->getOriginal('status'),
                'new_status' => $submission->status
            ]);

            return redirect()
                ->route('admin.submissions.index')
                ->with('success', 'Submission berhasil diperbarui!');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating submission by admin', [
                'submission_id' => $submission->id,
                'admin_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui submission.');
        }
    }

    /**
     * ✅ ENHANCED: Approve submission with better JSON response
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
            Cache::forget('submission_admin_stats');

            $message = 'Submission berhasil disetujui!';
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => $message,
                    'submission' => [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'approved_at' => $submission->approved_at->toISOString()
                    ]
                ]);
            }
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error approving submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = 'Terjadi kesalahan saat menyetujui submission.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * ✅ ENHANCED: Reject submission with flexible reason validation
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

        // ✅ ENHANCED: More flexible validation for reason
        $reason = $request->input('reason') ?? $request->input('rejection_reason');
        
        if (empty($reason) || strlen(trim($reason)) < 3) {
            $message = 'Alasan penolakan wajib diisi (minimal 3 karakter).';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        if (!$submission->canBeRejected()) {
            $message = 'Submission tidak dapat ditolak pada status saat ini.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        try {
            $submission->reject($user->id, trim($reason));

            Log::info('Submission rejected', [
                'submission_id' => $submission->id,
                'rejected_by' => $user->id,
                'reason' => $reason
            ]);

            // Clear relevant cache
            Cache::forget('admin_integrated_stats_' . $user->id);
            Cache::forget('admin_pending_submissions_' . $user->id);
            Cache::forget('submission_admin_stats');

            $message = 'Submission berhasil ditolak.';
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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = 'Terjadi kesalahan saat menolak submission.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * ✅ ENHANCED: Publish submission with comprehensive debugging and robust error handling
     */
    public function publish(ContentSubmission $submission)
    {
        $user = Auth::user();
        
        // Enhanced permission check dengan logging
        if (!$user->hasModeratorPrivileges()) {
            Log::warning('Unauthorized publish attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'submission_id' => $submission->id
            ]);
            
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        // Enhanced validation dengan detailed logging
        Log::info('Attempting to publish submission', [
            'submission_id' => $submission->id,
            'current_status' => $submission->status,
            'user_id' => $user->id,
            'payment_status' => $submission->payment ? $submission->payment->status : 'no_payment',
            'can_be_published' => $submission->canBePublished()
        ]);

        // Check individual conditions that might prevent publishing
        $validationErrors = [];
        
        if ($submission->status !== 'approved') {
            $validationErrors[] = "Status is '{$submission->status}', expected 'approved'";
        }
        
        if (!$submission->payment) {
            $validationErrors[] = "No payment record found";
        } elseif ($submission->payment->status !== 'confirmed') {
            $validationErrors[] = "Payment status is '{$submission->payment->status}', expected 'confirmed'";
        }
        
        if (!empty($validationErrors)) {
            $message = 'Submission tidak dapat dipublikasi: ' . implode(', ', $validationErrors);
            Log::warning('Publish validation failed', [
                'submission_id' => $submission->id,
                'errors' => $validationErrors
            ]);
            
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        try {
            DB::beginTransaction();
            
            // Langsung update status tanpa bergantung pada publishToGroup
            $submission->update([
                'status' => 'published',
                'published_by' => $user->id,
                'published_at' => now()
            ]);

            Log::info('Submission status updated to published', [
                'submission_id' => $submission->id,
                'published_by' => $user->id
            ]);

            // Attempt to publish to group (optional - tidak akan gagalkan proses jika error)
            try {
                $publishResult = $submission->publishToGroup();
                Log::info('Publish to group result', [
                    'submission_id' => $submission->id,
                    'success' => !!$publishResult
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to publish to group but continuing', [
                    'submission_id' => $submission->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Clear cache
            Cache::forget('admin_integrated_stats_' . $user->id);
            Cache::forget('admin_pending_submissions_' . $user->id);
            Cache::forget('submission_admin_stats');
            
            // Increment today's approved count for stats
            $todayKey = 'admin_total_approved_today';
            $todayCount = Cache::get($todayKey, 0);
            Cache::put($todayKey, $todayCount + 1, now()->endOfDay());
            
            // Create notification (optional)
            try {
                if (class_exists('\App\Models\Notification')) {
                    Notification::createSubmissionNotification($submission->id, 'submission_published');
                }
            } catch (\Exception $e) {
                Log::warning('Failed to create notification but continuing', [
                    'submission_id' => $submission->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            Log::info('Submission published successfully', [
                'submission_id' => $submission->id,
                'published_by' => $user->id,
                'title' => $submission->title
            ]);

            $message = 'Submission berhasil dipublikasi ke komunitas!';
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => $message,
                    'submission' => [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'published_at' => $submission->published_at->toISOString()
                    ]
                ]);
            }
            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error publishing submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = 'Terjadi kesalahan saat mempublikasi submission: ' . $e->getMessage();
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * ✅ Republish submission to community
     */
    public function republish(ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        if ($submission->status !== 'published') {
            $message = 'Hanya submission yang sudah dipublikasi yang dapat di-republish.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        try {
            // Use the model's republish method
            $submission->republish($user->id);

            Log::info('Submission republished', [
                'submission_id' => $submission->id,
                'republished_by' => $user->id
            ]);

            $message = 'Submission berhasil di-republish ke komunitas!';
            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error republishing submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = 'Terjadi kesalahan saat republish submission.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * ✅ Bulk action for submissions
     */
    public function bulkAction(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject,publish,delete',
            'submission_ids' => 'required|array',
            'submission_ids.*' => 'exists:content_submissions,id',
            'rejection_reason' => 'required_if:action,reject|string|max:1000'
        ]);

        try {
            $submissions = ContentSubmission::whereIn('id', $request->submission_ids)->get();
            $count = 0;

            foreach ($submissions as $submission) {
                switch ($request->action) {
                    case 'approve':
                        if ($submission->canBeApproved()) {
                            $submission->approve($user->id);
                            $count++;
                        }
                        break;
                        
                    case 'reject':
                        if ($submission->canBeRejected()) {
                            $submission->reject($user->id, $request->rejection_reason);
                            $count++;
                        }
                        break;
                        
                    case 'publish':
                        if ($submission->canBePublished()) {
                            $submission->publish($user->id);
                            $count++;
                        }
                        break;
                        
                    case 'delete':
                        if ($submission->canBeDeleted()) {
                            $submission->delete();
                            $count++;
                        }
                        break;
                }
            }

            // Clear cache
            Cache::forget('admin_integrated_stats_' . $user->id);
            Cache::forget('submission_admin_stats');

            return response()->json([
                'success' => true,
                'message' => "{$count} submission berhasil diproses."
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulk action', [
                'action' => $request->action,
                'submission_ids' => $request->submission_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses bulk action.'
            ], 500);
        }
    }

    /**
     * ✅ Export submissions data
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        try {
            $query = ContentSubmission::with(['user', 'category', 'payment']);
            
            // Apply filters if provided
            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            if ($request->category) {
                $query->where('category_id', $request->category);
            }
            
            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $submissions = $query->orderBy('created_at', 'desc')->get();

            // Generate CSV
            $filename = 'submissions_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($submissions) {
                $file = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($file, [
                    'ID',
                    'Judul',
                    'Deskripsi',
                    'Kategori',
                    'User',
                    'Email User',
                    'Status',
                    'Payment Amount',
                    'Payment Status',
                    'Tanggal Dibuat',
                    'Tanggal Disetujui',
                    'Tanggal Dipublikasi'
                ]);

                foreach ($submissions as $submission) {
                    fputcsv($file, [
                        $submission->id,
                        $submission->title,
                        Str::limit($submission->description, 100),
                        $submission->category->name ?? 'N/A',
                        $submission->user->full_name,
                        $submission->user->email,
                        $submission->status,
                        $submission->payment ? $submission->payment->amount : 'N/A',
                        $submission->payment ? $submission->payment->status : 'N/A',
                        $submission->created_at->format('Y-m-d H:i:s'),
                        $submission->approved_at ? $submission->approved_at->format('Y-m-d H:i:s') : 'N/A',
                        $submission->published_at ? $submission->published_at->format('Y-m-d H:i:s') : 'N/A'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting submissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengekspor data.');
        }
    }

    /**
     * ✅ Preview submission content
     */
    public function preview(ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $submission->load(['category', 'payment', 'user']);
        
        return view('admin.submissions.preview', compact('submission'));
    }

    /**
     * ✅ Duplicate submission
     */
    public function duplicate(ContentSubmission $submission)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        try {
            $duplicated = ContentSubmission::create([
                'user_id' => $submission->user_id,
                'category_id' => $submission->category_id,
                'title' => $submission->title . ' (Copy)',
                'description' => $submission->description,
                'attachment_path' => $submission->attachment_path,
                'attachment_type' => $submission->attachment_type,
                'attachment_name' => $submission->attachment_name,
                'attachment_size' => $submission->attachment_size,
                'status' => 'pending_payment',
            ]);

            Log::info('Submission duplicated', [
                'original_id' => $submission->id,
                'duplicated_id' => $duplicated->id,
                'duplicated_by' => $user->id
            ]);

            $message = 'Submission berhasil diduplikasi!';
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => $message,
                    'duplicated_id' => $duplicated->id
                ]);
            }
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error duplicating submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);

            $message = 'Terjadi kesalahan saat menduplikasi submission.';
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

    /**
     * ✅ Get admin statistics for API (separate method)
     */
    public function getAdminStats(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $stats = ContentSubmission::getAdminStats();
            
            // Add additional statistics
            $todayStats = [
                'today_submissions' => ContentSubmission::whereDate('created_at', today())->count(),
                'today_approved' => ContentSubmission::whereDate('approved_at', today())->count(),
                'today_published' => ContentSubmission::whereDate('published_at', today())->count(),
                'today_revenue' => 0
            ];

            // Get today's revenue if Payment model exists
            if (class_exists('\App\Models\Payment')) {
                $todayStats['today_revenue'] = Payment::where('status', 'confirmed')
                    ->whereDate('updated_at', today())
                    ->sum('amount') ?? 0;
            }

            return response()->json([
                'success' => true,
                'stats' => array_merge($stats, $todayStats)
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting admin stats', [
                'error' => $e->getMessage(),
                'admin_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get statistics'
            ], 500);
        }
    }
}