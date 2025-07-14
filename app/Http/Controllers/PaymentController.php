<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\Payment;
use App\Models\ContentSubmission;
use App\Models\Notification;

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

        // Check if payment already exists
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
     * Store payment information
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

            // Validation
            $request->validate([
                'payment_method' => ['required', 'in:transfer_bank,dana,gopay,ovo'],
                'payment_proof' => ['required', 'image', 'max:5120'], // 5MB max for payment proof
                'payment_details' => ['nullable', 'array'],
                'payment_details.sender_name' => ['required_if:payment_method,transfer_bank', 'string', 'max:255'],
                'payment_details.sender_account' => ['required_if:payment_method,transfer_bank', 'string', 'max:255'],
                'payment_details.transaction_id' => ['nullable', 'string', 'max:255'],
            ], [
                'payment_method.required' => 'Metode pembayaran wajib dipilih.',
                'payment_method.in' => 'Metode pembayaran tidak valid.',
                'payment_proof.required' => 'Bukti pembayaran wajib diunggah.',
                'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
                'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
                'payment_details.sender_name.required_if' => 'Nama pengirim wajib diisi untuk transfer bank.',
                'payment_details.sender_account.required_if' => 'Nomor rekening pengirim wajib diisi untuk transfer bank.',
            ]);

            // Handle payment proof upload
            $proofPath = null;
            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');
                $filename = time() . '_payment_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $proofPath = $file->storeAs('payment_proofs', $filename, 'public');
            }

            // Create or update payment
            $payment = Payment::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'submission_id' => $submission->id,
                ],
                [
                    'amount' => $submission->category->price,
                    'payment_method' => $request->payment_method,
                    'payment_proof_path' => $proofPath,
                    'status' => 'pending',
                    'payment_details' => $request->payment_details,
                ]
            );

            // Create notification for admin
            Notification::createPaymentNotification($payment->id, 'payment_submitted');

            Log::info('Payment submitted', [
                'payment_id' => $payment->id,
                'submission_id' => $submission->id,
                'user_id' => Auth::id(),
                'amount' => $payment->amount,
                'method' => $payment->payment_method
            ]);

            return redirect()
                ->route('payments.show', $payment)
                ->with('success', 'Pembayaran berhasil dikirim! Kami akan memverifikasi dalam 1x24 jam.');

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
        // Check ownership
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
     * Update payment information
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

            // Validation
            $request->validate([
                'payment_method' => ['required', 'in:transfer_bank,dana,gopay,ovo'],
                'payment_proof' => ['nullable', 'image', 'max:5120'],
                'payment_details' => ['nullable', 'array'],
                'payment_details.sender_name' => ['required_if:payment_method,transfer_bank', 'string', 'max:255'],
                'payment_details.sender_account' => ['required_if:payment_method,transfer_bank', 'string', 'max:255'],
                'payment_details.transaction_id' => ['nullable', 'string', 'max:255'],
            ]);

            // Handle new payment proof upload
            if ($request->hasFile('payment_proof')) {
                // Delete old proof
                $payment->deleteProof();
                
                // Upload new proof
                $file = $request->file('payment_proof');
                $filename = time() . '_payment_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $proofPath = $file->storeAs('payment_proofs', $filename, 'public');
                
                $payment->payment_proof_path = $proofPath;
            }

            // Update payment
            $payment->update([
                'payment_method' => $request->payment_method,
                'payment_details' => $request->payment_details,
                'status' => 'pending', // Reset to pending
                'rejection_reason' => null,
            ]);

            // Create notification for admin about resubmission
            Notification::createPaymentNotification($payment->id, 'payment_resubmitted');

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
     * Display admin payment dashboard
     */
    public function adminIndex(Request $request)
    {
        $user = Auth::user();
        
        // Check if user is admin
        if (!$user->isAdmin()) {
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
        
        // Get statistics
        $stats = Payment::getAdminStats();
        
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
     * Confirm payment
     */
    public function confirm(Payment $payment)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        if (!$payment->canBeConfirmed()) {
            return back()->with('error', 'Pembayaran tidak dapat dikonfirmasi pada status saat ini.');
        }

        $payment->confirm($user->id);

        Log::info('Payment confirmed', [
            'payment_id' => $payment->id,
            'confirmed_by' => $user->id,
            'submission_id' => $payment->submission_id
        ]);

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi!');
    }

    /**
     * Reject payment
     */
    public function reject(Request $request, Payment $payment)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500']
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        if (!$payment->canBeConfirmed()) {
            return back()->with('error', 'Pembayaran tidak dapat ditolak pada status saat ini.');
        }

        $payment->reject($user->id, $request->rejection_reason);

        Log::info('Payment rejected', [
            'payment_id' => $payment->id,
            'rejected_by' => $user->id,
            'reason' => $request->rejection_reason
        ]);

        return back()->with('success', 'Pembayaran telah ditolak.');
    }

    /**
     * Download payment proof
     */
    public function downloadProof(Payment $payment)
    {
        // Check access permissions
        if ($payment->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        if (!$payment->payment_proof_path) {
            abort(404, 'Payment proof not found');
        }

        $filePath = storage_path('app/public/' . $payment->payment_proof_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'Payment proof file not found');
        }

        $filename = 'bukti_pembayaran_' . $payment->id . '.' . pathinfo($payment->payment_proof_path, PATHINFO_EXTENSION);
        
        return response()->download($filePath, $filename);
    }

    /**
     * Get payment statistics for API
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        $stats = [
            'total_payments' => Payment::count(),
            'pending_payments' => Payment::pending()->count(),
            'confirmed_payments' => Payment::confirmed()->count(),
            'rejected_payments' => Payment::rejected()->count(),
            'total_revenue' => Payment::getRevenue($dateFrom, $dateTo),
            'revenue_breakdown' => [
                'transfer_bank' => Payment::confirmed()->where('payment_method', 'transfer_bank')->sum('amount'),
                'dana' => Payment::confirmed()->where('payment_method', 'dana')->sum('amount'),
                'gopay' => Payment::confirmed()->where('payment_method', 'gopay')->sum('amount'),
                'ovo' => Payment::confirmed()->where('payment_method', 'ovo')->sum('amount'),
            ],
            'daily_revenue' => Payment::confirmed()
                ->selectRaw('DATE(confirmed_at) as date, SUM(amount) as revenue')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get(),
        ];
        
        return response()->json($stats);
    }

    /**
     * Bulk actions for payments
     */
    public function bulkAction(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'action' => ['required', 'in:confirm,reject'],
            'payment_ids' => ['required', 'array'],
            'payment_ids.*' => ['integer', 'exists:payments,id'],
            'rejection_reason' => ['required_if:action,reject', 'string', 'min:10', 'max:500'],
        ]);

        $payments = Payment::whereIn('id', $request->payment_ids)->get();
        $successCount = 0;
        
        foreach ($payments as $payment) {
            if ($payment->canBeConfirmed()) {
                if ($request->action === 'confirm') {
                    $payment->confirm($user->id);
                    $successCount++;
                } elseif ($request->action === 'reject') {
                    $payment->reject($user->id, $request->rejection_reason);
                    $successCount++;
                }
            }
        }

        $actionText = $request->action === 'confirm' ? 'dikonfirmasi' : 'ditolak';
        
        return back()->with('success', "{$successCount} pembayaran berhasil {$actionText}.");
    }
}