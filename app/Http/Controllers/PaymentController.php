<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use App\Models\Payment;
use App\Models\ContentSubmission;
use App\Models\Notification;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Show payment form for submission
     */
    public function create(ContentSubmission $submission)
    {
        // Check ownership
        if ($submission->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        // Check if submission can be paid
        if (!$submission->canBePaid()) {
            return redirect()
                ->route('submissions.show', $submission)
                ->with('error', 'Konten tidak memerlukan pembayaran pada status saat ini.');
        }

        // Check if payment already exists and not rejected
        if ($submission->payment && $submission->payment->status !== 'rejected') {
            return redirect()
                ->route('payments.show', $submission->payment)
                ->with('info', 'Pembayaran untuk konten ini sudah ada.');
        }

        $submission->load('category');
        $paymentMethods = Payment::getPaymentMethods();
        
        return view('payments.create', compact('submission', 'paymentMethods'));
    }

    /**
     * ✅ UPDATED: Store payment information dengan ShopeePay validation
     */
    public function store(Request $request, ContentSubmission $submission)
    {
        try {
            // Check ownership
            if ($submission->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access');
            }

            // Check if submission can be paid
            if (!$submission->canBePaid()) {
                return redirect()
                    ->route('submissions.show', $submission)
                    ->with('error', 'Konten tidak memerlukan pembayaran pada status saat ini.');
            }

            // ✅ UPDATED: Validation rules untuk ShopeePay dan e-wallet
            $request->validate([
                'payment_method' => ['required', 'in:shopeepay,dana,gopay,ovo'],
                'payment_proof' => ['required', 'image', 'max:5120'], // 5MB max for payment proof
                'payment_details' => ['nullable', 'array'],
                'payment_details.sender_name' => ['nullable', 'string', 'max:255'],
                'payment_details.sender_account' => ['nullable', 'string', 'max:255'],
                'payment_details.transaction_id' => ['nullable', 'string', 'max:255'],
            ], [
                'payment_method.required' => 'Metode pembayaran wajib dipilih.',
                'payment_method.in' => 'Metode pembayaran tidak valid.',
                'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
                'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
                'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
            ]);

            // Handle payment proof upload using consistent method
            $proofData = $this->handleFileUpload($request->file('payment_proof'), 'payment_proofs');

            // Check if payment already exists for this submission
            $existingPayment = $submission->payment;
            
            if ($existingPayment && $existingPayment->status === 'rejected') {
                // Update existing rejected payment
                $existingPayment->update([
                    'amount' => $submission->category->price,
                    'payment_method' => $request->payment_method,
                    'payment_proof_path' => $proofData['path'],
                    'status' => 'pending',
                    'payment_details' => $request->payment_details ?? [],
                    'rejection_reason' => null,
                    'confirmed_by' => null,
                    'confirmed_at' => null,
                ]);
                $payment = $existingPayment;
            } else {
                // Create new payment
                $payment = Payment::create([
                    'user_id' => Auth::id(),
                    'submission_id' => $submission->id,
                    'amount' => $submission->category->price,
                    'payment_method' => $request->payment_method,
                    'payment_proof_path' => $proofData['path'],
                    'status' => 'pending',
                    'payment_details' => $request->payment_details ?? [],
                ]);
            }

            // Update submission payment reference
            $submission->update(['payment_id' => $payment->id]);

            // ✅ IMPROVED: Create notification with error handling
            if (class_exists('\App\Models\Notification')) {
                try {
                    Notification::createPaymentNotification($payment->id, 'payment_pending');
                } catch (\Exception $e) {
                    // Log notification error but don't fail the payment
                    Log::warning('Failed to create payment notification', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Payment submitted', [
                'payment_id' => $payment->id,
                'submission_id' => $submission->id,
                'user_id' => Auth::id(),
                'amount' => $payment->amount,
                'method' => $payment->payment_method
            ]);

            return redirect()
                ->route('payments.show', $payment)
                ->with('success', 'Bukti pembayaran berhasil diunggah! Admin akan memverifikasi dalam 1x24 jam.');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error submitting payment', [
                'submission_id' => $submission->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat mengirim pembayaran. Silakan coba lagi.');
        }
    }

    /**
     * Display payment details
     */
    public function show(Payment $payment)
    {
        // Check ownership or admin/moderator privileges
        if ($payment->user_id !== Auth::id() && !Auth::user()->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $payment->load(['user', 'submission.category', 'confirmedBy']);
        
        return view('payments.show', compact('payment'));
    }

    /**
     * Show payment edit form (for rejected payments)
     */
    public function edit(Payment $payment)
    {
        // Check ownership
        if ($payment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        // Only allow editing rejected payments
        if ($payment->status !== 'rejected') {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', 'Pembayaran tidak dapat diedit pada status saat ini.');
        }

        $payment->load('submission.category');
        $paymentMethods = Payment::getPaymentMethods();
        
        return view('payments.edit', compact('payment', 'paymentMethods'));
    }

    /**
     * ✅ UPDATED: Update payment information dengan ShopeePay validation
     */
    public function update(Request $request, Payment $payment)
    {
        try {
            // Check ownership
            if ($payment->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access');
            }

            // Only allow updating rejected payments
            if ($payment->status !== 'rejected') {
                return redirect()
                    ->route('payments.show', $payment)
                    ->with('error', 'Pembayaran tidak dapat diperbarui pada status saat ini.');
            }

            // ✅ UPDATED: Validation rules untuk ShopeePay dan e-wallet
            $request->validate([
                'payment_method' => ['required', 'in:shopeepay,dana,gopay,ovo'],
                'payment_proof' => ['nullable', 'image', 'max:5120'],
                'payment_details' => ['nullable', 'array'],
                'payment_details.sender_name' => ['nullable', 'string', 'max:255'],
                'payment_details.sender_account' => ['nullable', 'string', 'max:255'],
                'payment_details.transaction_id' => ['nullable', 'string', 'max:255'],
            ]);

            // Handle new payment proof upload if provided
            if ($request->hasFile('payment_proof')) {
                // Delete old proof
                $payment->deleteProof();
                
                // Upload new proof using consistent method
                $proofData = $this->handleFileUpload($request->file('payment_proof'), 'payment_proofs');
                $payment->payment_proof_path = $proofData['path'];
            }

            // Update payment
            $payment->update([
                'payment_method' => $request->payment_method,
                'payment_details' => $request->payment_details ?? [],
                'status' => 'pending', // Reset to pending
                'rejection_reason' => null,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);

            // ✅ IMPROVED: Create notification with error handling
            if (class_exists('\App\Models\Notification')) {
                try {
                    Notification::createPaymentNotification($payment->id, 'payment_pending');
                } catch (\Exception $e) {
                    Log::warning('Failed to create payment notification', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return redirect()
                ->route('payments.show', $payment)
                ->with('success', 'Pembayaran berhasil diperbarui dan dikirim ulang untuk verifikasi.');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui pembayaran. Silakan coba lagi.');
        }
    }

    /**
     * Download payment proof
     */
    public function downloadProof(Payment $payment)
    {
        // Check access permissions
        if ($payment->user_id !== Auth::id() && !Auth::user()->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        if (!$payment->payment_proof_path || !Storage::disk('public')->exists($payment->payment_proof_path)) {
            abort(404, 'Payment proof not found');
        }

        $filename = 'bukti_pembayaran_' . $payment->id . '_' . now()->format('Y-m-d') . '.' . pathinfo($payment->payment_proof_path, PATHINFO_EXTENSION);
        
        return Storage::disk('public')->download($payment->payment_proof_path, $filename);
    }

    /**
     * ✅ ADDED: Handle file upload - consistent with ContentSubmissionController
     */
    private function handleFileUpload($file, $directory = 'uploads')
    {
        try {
            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
            
            // Store file
            $path = $file->storeAs($directory, $filename, 'public');
            
            return [
                'path' => $path,
                'name' => $originalName,
                'type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        } catch (\Exception $e) {
            Log::error('File upload error', [
                'error' => $e->getMessage(),
                'file' => $originalName ?? 'unknown'
            ]);
            
            throw new \Exception('Gagal mengupload file. Silakan coba lagi.');
        }
    }

    // ===== ADMIN METHODS =====

    /**
     * ✅ FIXED: Display admin payment dashboard with complete stats
     */
    public function adminIndex(Request $request)
    {
        $user = Auth::user();
        
        // ✅ UPDATED: Use hasModeratorPrivileges instead of just isAdmin
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        // Get filters
        $status = $request->get('status', 'pending');
        $method = $request->get('method');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        // Build query
        $query = Payment::with(['user', 'submission.category', 'confirmedBy']);
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($method) {
            $query->where('payment_method', $method);
        }
        
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // ✅ FIXED: Get comprehensive statistics that match view expectations
        $stats = $this->getCompleteAdminStats();
        
        return view('admin.payments.index', compact(
            'payments', 
            'stats',
            'status',
            'method',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * ✅ NEW: Get complete admin statistics for view compatibility
     */
    private function getCompleteAdminStats()
    {
        // Get current month revenue
        $revenueThisMonth = Payment::where('status', 'confirmed')
            ->whereMonth('confirmed_at', now()->month)
            ->whereYear('confirmed_at', now()->year)
            ->sum('amount');

        // Get last month revenue for comparison
        $revenueLastMonth = Payment::where('status', 'confirmed')
            ->whereMonth('confirmed_at', now()->subMonth()->month)
            ->whereYear('confirmed_at', now()->subMonth()->year)
            ->sum('amount');

        // Calculate growth percentage
        $revenueGrowth = 0;
        if ($revenueLastMonth > 0) {
            $revenueGrowth = round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1);
        } elseif ($revenueThisMonth > 0) {
            $revenueGrowth = 100;
        }

        // Get today's stats
        $todayRevenue = Payment::where('status', 'confirmed')
            ->whereDate('confirmed_at', today())
            ->sum('amount');

        $todayPayments = Payment::where('status', 'confirmed')
            ->whereDate('confirmed_at', today())
            ->count();

        // Get week stats
        $weekRevenue = Payment::where('status', 'confirmed')
            ->whereBetween('confirmed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount');

        $weekPayments = Payment::where('status', 'confirmed')
            ->whereBetween('confirmed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return [
            // Basic counts
            'total_payments' => Payment::count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'confirmed_payments' => Payment::where('status', 'confirmed')->count(),
            'rejected_payments' => Payment::where('status', 'rejected')->count(),
            
            // Revenue stats
            'total_revenue' => Payment::where('status', 'confirmed')->sum('amount'),
            'revenue_this_month' => $revenueThisMonth,
            'revenue_last_month' => $revenueLastMonth,
            'revenue_growth' => $revenueGrowth,
            'today_revenue' => $todayRevenue,
            'week_revenue' => $weekRevenue,
            
            // Payment counts
            'today_payments' => $todayPayments,
            'week_payments' => $weekPayments,
            'this_month_payments' => Payment::where('status', 'confirmed')
                ->whereMonth('confirmed_at', now()->month)
                ->whereYear('confirmed_at', now()->year)
                ->count(),
            
            // Average stats
            'average_payment_amount' => Payment::where('status', 'confirmed')
                ->avg('amount') ?: 0,
            
            // Method breakdown
            'method_breakdown' => Payment::where('status', 'confirmed')
                ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('payment_method')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->payment_method => [
                        'count' => $item->count,
                        'total' => $item->total
                    ]];
                }),
                
            // Recent activity
            'recent_confirmations' => Payment::where('status', 'confirmed')
                ->where('confirmed_at', '>=', now()->subHours(24))
                ->count(),
        ];
    }

    /**
     * ✅ ENHANCED: Confirm payment - support JSON response untuk dashboard admin
     */
    public function confirm(Payment $payment)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        if (!$payment->canBeConfirmed()) {
            $message = 'Pembayaran tidak dapat dikonfirmasi pada status saat ini.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        try {
            $payment->confirm($user->id);

            Log::info('Payment confirmed', [
                'payment_id' => $payment->id,
                'confirmed_by' => $user->id,
                'submission_id' => $payment->submission_id
            ]);

            // Clear relevant cache
            Cache::forget('admin_integrated_stats_' . $user->id);
            Cache::forget('admin_pending_payments_' . $user->id);

            $message = 'Pembayaran berhasil dikonfirmasi!';
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => $message,
                    'payment' => [
                        'id' => $payment->id,
                        'status' => $payment->status,
                        'confirmed_at' => $payment->updated_at
                    ]
                ]);
            }
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error confirming payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            $message = 'Terjadi kesalahan saat mengkonfirmasi pembayaran.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * ✅ ENHANCED: Reject payment - support JSON response untuk dashboard admin
     */
    public function reject(Request $request, Payment $payment)
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

        if (!$payment->canBeRejected()) {
            $message = 'Pembayaran tidak dapat ditolak pada status saat ini.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return back()->with('error', $message);
        }

        try {
            $payment->reject($request->rejection_reason, $user->id);

            Log::info('Payment rejected', [
                'payment_id' => $payment->id,
                'rejected_by' => $user->id,
                'reason' => $request->rejection_reason
            ]);

            // Clear relevant cache
            Cache::forget('admin_integrated_stats_' . $user->id);
            Cache::forget('admin_pending_payments_' . $user->id);

            $message = 'Pembayaran berhasil ditolak.';
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => $message,
                    'payment' => [
                        'id' => $payment->id,
                        'status' => $payment->status,
                        'rejection_reason' => $payment->rejection_reason
                    ]
                ]);
            }
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error rejecting payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            $message = 'Terjadi kesalahan saat menolak pembayaran.';
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * ✅ ENHANCED: Get payment statistics for API
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $stats = $this->getCompleteAdminStats();
        
        return response()->json($stats);
    }

    /**
     * ✅ ENHANCED: Bulk actions for payments
     */
    public function bulkAction(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'action' => ['required', 'in:confirm,reject'],
            'payment_ids' => ['required', 'array'],
            'payment_ids.*' => ['integer', 'exists:payments,id'],
            'rejection_reason' => ['required_if:action,reject', 'string', 'min:10', 'max:500'],
        ]);

        $paymentIds = $request->payment_ids;
        $action = $request->action;
        $successCount = 0;
        
        foreach ($paymentIds as $paymentId) {
            $payment = Payment::find($paymentId);
            
            if (!$payment) continue;

            if ($action === 'confirm' && $payment->canBeConfirmed()) {
                $payment->confirm($user->id);
                $successCount++;
            } elseif ($action === 'reject' && $payment->canBeRejected()) {
                $payment->reject($request->rejection_reason, $user->id);
                $successCount++;
            }
        }

        // Clear relevant cache
        Cache::forget('admin_integrated_stats_' . $user->id);
        Cache::forget('admin_pending_payments_' . $user->id);

        $actionText = $action === 'confirm' ? 'dikonfirmasi' : 'ditolak';
        
        return back()->with('success', "{$successCount} pembayaran berhasil {$actionText}.");
    }

    /**
     * ✅ ADDED: Get payment methods for API (useful for frontend)
     */
    public function getPaymentMethods()
    {
        $paymentMethods = Payment::getPaymentMethods();
        
        return response()->json([
            'payment_methods' => $paymentMethods
        ]);
    }

    /**
     * ✅ ADDED: Get user payment history
     */
    public function userPayments(Request $request)
    {
        $user = Auth::user();
        
        $payments = Payment::where('user_id', $user->id)
                          ->with(['submission.category'])
                          ->orderBy('created_at', 'desc')
                          ->paginate(10);
        
        $stats = [
            'total' => $user->payments()->count(),
            'pending' => $user->payments()->where('status', 'pending')->count(),
            'confirmed' => $user->payments()->where('status', 'confirmed')->count(),
            'rejected' => $user->payments()->where('status', 'rejected')->count(),
            'total_spent' => $user->payments()->where('status', 'confirmed')->sum('amount'),
        ];
        
        return view('payments.user-history', compact('payments', 'stats'));
    }

    /**
     * ✅ ADDED: Export payments to CSV (for admin)
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $query = Payment::with(['user', 'submission.category']);
        
        // Apply filters
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'payments_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($payments) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'ID', 'User', 'Submission', 'Category', 'Amount', 
                'Method', 'Status', 'Created At', 'Confirmed At'
            ]);
            
            // Data
            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->id,
                    $payment->user->full_name,
                    $payment->submission->title,
                    $payment->submission->category->name,
                    $payment->amount,
                    $payment->payment_method_display,
                    $payment->status_display,
                    $payment->created_at->format('Y-m-d H:i:s'),
                    $payment->confirmed_at ? $payment->confirmed_at->format('Y-m-d H:i:s') : '',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * ✅ ADDED: Get payment statistics by date range
     */
    public function getStatsByDateRange(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModeratorPrivileges()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $stats = [
            'total_payments' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'confirmed_payments' => Payment::confirmed()->whereBetween('confirmed_at', [$dateFrom, $dateTo])->count(),
            'total_revenue' => Payment::confirmed()->whereBetween('confirmed_at', [$dateFrom, $dateTo])->sum('amount'),
            'daily_breakdown' => Payment::confirmed()
                ->whereBetween('confirmed_at', [$dateFrom, $dateTo])
                ->selectRaw('DATE(confirmed_at) as date, COUNT(*) as count, SUM(amount) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'method_breakdown' => Payment::confirmed()
                ->whereBetween('confirmed_at', [$dateFrom, $dateTo])
                ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as revenue')
                ->groupBy('payment_method')
                ->get(),
        ];

        return response()->json($stats);
    }
}