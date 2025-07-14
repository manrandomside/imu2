<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'submission_id',
        'amount',
        'payment_method',
        'payment_proof_path',
        'status',
        'confirmed_by',
        'confirmed_at',
        'rejection_reason',
        'payment_details',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'payment_details' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submission()
    {
        return $this->belongsTo(ContentSubmission::class, 'submission_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Accessors & Mutators
     */
    public function getPaymentProofUrlAttribute()
    {
        if (!$this->payment_proof_path) {
            return null;
        }
        
        return asset('storage/' . $this->payment_proof_path);
    }

    public function getStatusBadgeColorAttribute()
    {
        $colors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];

        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusDisplayAttribute()
    {
        $statuses = [
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'rejected' => 'Ditolak',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusIconAttribute()
    {
        $icons = [
            'pending' => '⏳',
            'confirmed' => '✅',
            'rejected' => '❌',
        ];

        return $icons[$this->status] ?? '💰';
    }

    public function getPaymentMethodDisplayAttribute()
    {
        $methods = [
            'transfer_bank' => 'Transfer Bank',
            'dana' => 'DANA',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
        ];

        return $methods[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * ✅ ADDED: Enhanced file size accessor
     */
    public function getFormattedProofFileSizeAttribute()
    {
        $bytes = $this->getProofFileSize();
        
        if ($bytes === 0) return null;
        
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * ✅ ADDED: Additional accessors for better UI support
     */
    public function getMethodIconAttribute()
    {
        $icons = [
            'transfer_bank' => 'fas fa-university',
            'dana' => 'fas fa-mobile-alt',
            'gopay' => 'fas fa-mobile-alt',
            'ovo' => 'fas fa-mobile-alt',
        ];

        return $icons[$this->payment_method] ?? 'fas fa-credit-card';
    }

    public function getConfirmedAtHumanAttribute()
    {
        return $this->confirmed_at ? $this->confirmed_at->diffForHumans() : null;
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * ✅ ADDED: Additional scopes for better filtering
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByAmount($query, $amount)
    {
        return $query->where('amount', $amount);
    }

    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('amount', [$minAmount, $maxAmount]);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('created_at', now()->year);
    }

    public function scopeWithProof($query)
    {
        return $query->whereNotNull('payment_proof_path');
    }

    public function scopeWithoutProof($query)
    {
        return $query->whereNull('payment_proof_path');
    }

    /**
     * Status Check Methods
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Business Logic Methods
     */
    public function canBeConfirmed()
    {
        return $this->status === 'pending';
    }

    /**
     * ✅ ENHANCED: Updated canBeRejected() method 
     */
    public function canBeRejected()
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * ✅ ADDED: Additional validation methods
     */
    public function canBeEdited()
    {
        return $this->status === 'rejected';
    }

    public function canBeDeleted()
    {
        return in_array($this->status, ['pending', 'rejected']);
    }

    public function requiresAction()
    {
        return $this->status === 'pending';
    }

    /**
     * ✅ ENHANCED: Improved confirm method with better notification handling
     */
    public function confirm($adminId)
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_by' => $adminId,
            'confirmed_at' => now(),
            'rejection_reason' => null,
        ]);

        // Update submission status
        if ($this->submission) {
            $this->submission->markAsPaid($this->id);
        }

        // Create notification for user
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::createForUser(
                $this->user_id,
                'Pembayaran Dikonfirmasi',
                "Pembayaran sebesar {$this->formatted_amount} telah dikonfirmasi. Konten Anda sedang dalam proses review.",
                'payment_confirmed',
                ['payment_id' => $this->id]
            );
        }

        return $this;
    }

    /**
     * ✅ ENHANCED: Improved reject method with better parameters
     */
    public function reject($reason, $adminId = null)
    {
        $this->update([
            'status' => 'rejected',
            'confirmed_by' => $adminId,
            'confirmed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Update submission status back to pending payment
        if ($this->submission) {
            $this->submission->update([
                'status' => 'pending_payment',
                'payment_id' => null,
                'submitted_at' => null,
            ]);
        }

        // Create notification for user
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::createForUser(
                $this->user_id,
                'Pembayaran Ditolak',
                "Pembayaran sebesar {$this->formatted_amount} ditolak. Alasan: {$reason}",
                'payment_rejected',
                ['payment_id' => $this->id]
            );
        }

        return $this;
    }

    /**
     * ✅ ENHANCED: Method to reset payment for resubmission
     */
    public function resetForResubmission()
    {
        // Delete old proof file if exists
        $this->deleteProof();
        
        // Reset payment status
        $this->update([
            'status' => 'pending',
            'confirmed_by' => null,
            'confirmed_at' => null,
            'rejection_reason' => null,
            'payment_proof_path' => null,
            'payment_details' => null,
        ]);
        
        return $this;
    }

    /**
     * File Management Methods
     */
    public function deleteProof()
    {
        if ($this->payment_proof_path && Storage::disk('public')->exists($this->payment_proof_path)) {
            Storage::disk('public')->delete($this->payment_proof_path);
        }
        
        $this->update(['payment_proof_path' => null]);
        
        return $this;
    }

    /**
     * ✅ ENHANCED: Check if payment has valid proof file
     */
    public function hasValidProof()
    {
        if (!$this->payment_proof_path) {
            return false;
        }

        return Storage::disk('public')->exists($this->payment_proof_path);
    }

    /**
     * ✅ ENHANCED: Get file size of payment proof
     */
    public function getProofFileSize()
    {
        if (!$this->hasValidProof()) {
            return 0;
        }

        return Storage::disk('public')->size($this->payment_proof_path);
    }

    /**
     * ✅ ADDED: Validate payment proof file
     */
    public function validateProofFile()
    {
        $errors = [];

        if (!$this->payment_proof_path) {
            $errors[] = 'Bukti pembayaran wajib diunggah.';
            return $errors;
        }

        if (!$this->hasValidProof()) {
            $errors[] = 'File bukti pembayaran tidak ditemukan atau rusak.';
            return $errors;
        }

        // Check file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($this->getProofFileSize() > $maxSize) {
            $errors[] = 'Ukuran file bukti pembayaran terlalu besar. Maksimal 5MB.';
        }

        return $errors;
    }

    /**
     * ✅ ADDED: Payment validation methods
     */
    public function validateForConfirmation()
    {
        $errors = [];

        if (!$this->canBeConfirmed()) {
            $errors[] = 'Pembayaran tidak dapat dikonfirmasi pada status saat ini.';
        }

        if (!$this->hasValidProof()) {
            $errors[] = 'Bukti pembayaran tidak valid atau tidak ditemukan.';
        }

        if (!$this->submission) {
            $errors[] = 'Submission terkait tidak ditemukan.';
        }

        return $errors;
    }

    public function isValid()
    {
        return empty($this->validateForConfirmation());
    }

    /**
     * ✅ ENHANCED: Static methods with better functionality
     */
    public static function getPaymentMethods()
    {
        return [
            'transfer_bank' => [
                'name' => 'Transfer Bank',
                'description' => 'Transfer ke rekening bank IMU',
                'icon' => 'fas fa-university',
                'details' => [
                    'bank' => 'Bank BCA',
                    'account_number' => '1234567890',
                    'account_name' => 'IMU - Universitas Udayana'
                ]
            ],
            'dana' => [
                'name' => 'DANA',
                'description' => 'Transfer melalui aplikasi DANA',
                'icon' => 'fas fa-mobile-alt',
                'details' => [
                    'phone' => '08123456789',
                    'name' => 'IMU Udayana'
                ]
            ],
            'gopay' => [
                'name' => 'GoPay',
                'description' => 'Transfer melalui aplikasi Gojek',
                'icon' => 'fas fa-mobile-alt',
                'details' => [
                    'phone' => '08123456789',
                    'name' => 'IMU Udayana'
                ]
            ],
            'ovo' => [
                'name' => 'OVO',
                'description' => 'Transfer melalui aplikasi OVO',
                'icon' => 'fas fa-mobile-alt',
                'details' => [
                    'phone' => '08123456789',
                    'name' => 'IMU Udayana'
                ]
            ]
        ];
    }

    public static function getStatusOptions()
    {
        return [
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'rejected' => 'Ditolak',
        ];
    }

    /**
     * ✅ ENHANCED: Revenue calculation with date filtering
     */
    public static function getRevenue($startDate = null, $endDate = null)
    {
        $query = static::confirmed();
        
        if ($startDate) {
            $query->whereDate('confirmed_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('confirmed_at', '<=', $endDate);
        }
        
        return $query->sum('amount') ?? 0;
    }

    /**
     * ✅ ENHANCED: Comprehensive admin statistics (required by ContentSubmissionController)
     */
    public static function getAdminStats()
    {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'rejected' => 0,
            'total_revenue' => 0,
            'today_revenue' => 0,
            'this_week_revenue' => 0,
            'this_month_revenue' => 0,
            'today_count' => 0,
            'this_week_count' => 0,
            'this_month_count' => 0,
        ];

        // Status counts
        $payments = static::select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as revenue'))
                          ->groupBy('status')
                          ->get();

        foreach ($payments as $payment) {
            $stats[$payment->status] = $payment->count;
            $stats['total'] += $payment->count;
            
            if ($payment->status === 'confirmed') {
                $stats['total_revenue'] = $payment->revenue ?? 0;
            }
        }

        // Time-based revenue
        $stats['today_revenue'] = static::confirmed()
                                       ->whereDate('confirmed_at', today())
                                       ->sum('amount') ?? 0;

        $stats['this_week_revenue'] = static::confirmed()
                                           ->whereBetween('confirmed_at', [now()->startOfWeek(), now()->endOfWeek()])
                                           ->sum('amount') ?? 0;

        $stats['this_month_revenue'] = static::confirmed()
                                            ->whereMonth('confirmed_at', now()->month)
                                            ->whereYear('confirmed_at', now()->year)
                                            ->sum('amount') ?? 0;

        // Time-based counts
        $stats['today_count'] = static::whereDate('created_at', today())->count();
        $stats['this_week_count'] = static::thisWeek()->count();
        $stats['this_month_count'] = static::thisMonth()->count();

        return $stats;
    }

    /**
     * ✅ ENHANCED: Get payment statistics for specific user
     */
    public static function getUserStats($userId)
    {
        return [
            'total_payments' => static::where('user_id', $userId)->count(),
            'pending_payments' => static::where('user_id', $userId)->pending()->count(),
            'confirmed_payments' => static::where('user_id', $userId)->confirmed()->count(),
            'rejected_payments' => static::where('user_id', $userId)->rejected()->count(),
            'total_amount_paid' => static::where('user_id', $userId)->confirmed()->sum('amount') ?? 0,
            'latest_payment' => static::where('user_id', $userId)->latest()->first(),
        ];
    }

    /**
     * ✅ ADDED: Additional analytics methods
     */
    public static function getPaymentTrends($days = 30)
    {
        return static::selectRaw('DATE(created_at) as date, count(*) as count, sum(amount) as revenue')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
    }

    public static function getMethodStats()
    {
        return static::select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as revenue'))
                    ->groupBy('payment_method')
                    ->get()
                    ->map(function ($item) {
                        $methods = static::getPaymentMethods();
                        return [
                            'method' => $item->payment_method,
                            'name' => $methods[$item->payment_method]['name'] ?? ucfirst($item->payment_method),
                            'count' => $item->count,
                            'revenue' => $item->revenue ?? 0
                        ];
                    });
    }

    public static function getAveragePaymentAmount()
    {
        $confirmed = static::confirmed();
        $count = $confirmed->count();
        
        if ($count === 0) {
            return 0;
        }
        
        return $confirmed->sum('amount') / $count;
    }

    public static function getPendingOlderThan($hours = 24)
    {
        return static::pending()
                    ->where('created_at', '<', now()->subHours($hours))
                    ->with(['user', 'submission'])
                    ->get();
    }

    /**
     * ✅ ENHANCED: Bulk operations with better error handling
     */
    public static function bulkConfirm(array $paymentIds, $adminId)
    {
        $confirmed = 0;
        $errors = [];
        
        foreach ($paymentIds as $paymentId) {
            $payment = static::find($paymentId);
            
            if (!$payment) {
                $errors[] = "Payment ID {$paymentId} not found.";
                continue;
            }
            
            if (!$payment->canBeConfirmed()) {
                $errors[] = "Payment ID {$paymentId} cannot be confirmed.";
                continue;
            }
            
            try {
                $payment->confirm($adminId);
                $confirmed++;
            } catch (\Exception $e) {
                $errors[] = "Failed to confirm payment ID {$paymentId}: " . $e->getMessage();
            }
        }
        
        return [
            'confirmed' => $confirmed,
            'errors' => $errors
        ];
    }

    /**
     * ✅ ENHANCED: Bulk reject with better error handling
     */
    public static function bulkReject(array $paymentIds, $reason, $adminId = null)
    {
        $rejected = 0;
        $errors = [];
        
        foreach ($paymentIds as $paymentId) {
            $payment = static::find($paymentId);
            
            if (!$payment) {
                $errors[] = "Payment ID {$paymentId} not found.";
                continue;
            }
            
            if (!$payment->canBeRejected()) {
                $errors[] = "Payment ID {$paymentId} cannot be rejected.";
                continue;
            }
            
            try {
                $payment->reject($reason, $adminId);
                $rejected++;
            } catch (\Exception $e) {
                $errors[] = "Failed to reject payment ID {$paymentId}: " . $e->getMessage();
            }
        }
        
        return [
            'rejected' => $rejected,
            'errors' => $errors
        ];
    }

    /**
     * ✅ ADDED: Search and filtering methods
     */
    public static function search($query)
    {
        return static::whereHas('user', function($q) use ($query) {
            $q->where('full_name', 'LIKE', "%{$query}%")
              ->orWhere('email', 'LIKE', "%{$query}%");
        })->orWhereHas('submission', function($q) use ($query) {
            $q->where('title', 'LIKE', "%{$query}%");
        })->orWhere('payment_method', 'LIKE', "%{$query}%");
    }

    public static function filterByDateRange($startDate, $endDate)
    {
        return static::whereBetween('created_at', [$startDate, $endDate]);
    }

    public static function getRecentPayments($limit = 10)
    {
        return static::with(['user', 'submission', 'confirmedBy'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * ✅ ADDED: Export functionality
     */
    public function getExportData()
    {
        return [
            'id' => $this->id,
            'user_name' => $this->user->full_name ?? 'Unknown',
            'user_email' => $this->user->email ?? 'Unknown',
            'submission_title' => $this->submission->title ?? 'Unknown',
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'payment_method' => $this->payment_method_display,
            'status' => $this->status_display,
            'confirmed_by' => $this->confirmedBy->full_name ?? null,
            'confirmed_at' => $this->confirmed_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}