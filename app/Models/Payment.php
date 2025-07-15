<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'payment_details',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
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
     * ✅ ADDED: Relationship untuk rejected_by
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
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

    /**
     * ✅ UPDATED: Payment method display untuk ShopeePay dan e-wallet lainnya
     */
    public function getPaymentMethodDisplayAttribute()
    {
        $methods = [
            'shopeepay' => 'ShopeePay',
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
     * ✅ UPDATED: Method icon untuk ShopeePay dan e-wallet
     */
    public function getMethodIconAttribute()
    {
        $icons = [
            'shopeepay' => 'fas fa-shopping-bag',
            'dana' => 'fas fa-mobile-alt',
            'gopay' => 'fas fa-motorcycle',
            'ovo' => 'fas fa-wallet',
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
     * ✅ ADDED: Rejected at human readable
     */
    public function getRejectedAtHumanAttribute()
    {
        return $this->rejected_at ? $this->rejected_at->diffForHumans() : null;
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

    /**
     * ✅ UPDATED: Payment methods dengan ShopeePay dan nomor 082144715831
     */
    public static function getPaymentMethods()
    {
        return [
            'shopeepay' => [
                'name' => 'ShopeePay',
                'details' => 'Transfer ke ShopeePay 082144715831',
                'icon' => 'fas fa-shopping-bag',
                'phone' => '082144715831'
            ],
            'dana' => [
                'name' => 'DANA',
                'details' => 'Transfer ke DANA 082144715831',
                'icon' => 'fas fa-mobile-alt',
                'phone' => '082144715831'
            ],
            'gopay' => [
                'name' => 'GoPay',
                'details' => 'Transfer ke GoPay 082144715831',
                'icon' => 'fas fa-motorcycle',
                'phone' => '082144715831'
            ],
            'ovo' => [
                'name' => 'OVO',
                'details' => 'Transfer ke OVO 082144715831',
                'icon' => 'fas fa-wallet',
                'phone' => '082144715831'
            ]
        ];
    }

    /**
     * ✅ HELPER: Get payment method details by key
     */
    public static function getPaymentMethodDetails($methodKey)
    {
        $methods = static::getPaymentMethods();
        return $methods[$methodKey] ?? null;
    }

    /**
     * ✅ HELPER: Get available payment method keys
     */
    public static function getAvailablePaymentMethods()
    {
        return array_keys(static::getPaymentMethods());
    }

    /**
     * ✅ VALIDATION: Check if payment method is valid
     */
    public static function isValidPaymentMethod($method)
    {
        return in_array($method, static::getAvailablePaymentMethods());
    }

    /**
     * Business Logic Methods
     */
    public function canBeConfirmed()
    {
        return $this->status === 'pending';
    }

    public function canBeRejected()
    {
        return $this->status === 'pending';
    }

    public function canBeUpdated()
    {
        return $this->status === 'rejected';
    }

    /**
     * ✅ ADDED: Missing isConfirmed() method and other status check methods
     */
    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    /**
     * ✅ ADDITIONAL HELPER METHODS: Status checking methods
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * ✅ ENHANCED: Check if payment has been processed (confirmed or rejected)
     */
    public function isProcessed()
    {
        return in_array($this->status, ['confirmed', 'rejected']);
    }

    /**
     * ✅ ENHANCED: Check if payment is waiting for action
     */
    public function isWaiting()
    {
        return $this->status === 'pending';
    }

    /**
     * ✅ ENHANCED: Confirm payment - compatible dengan dashboard admin
     */
    public function confirm($confirmedBy = null)
    {
        if (!$this->canBeConfirmed()) {
            throw new \Exception('Payment cannot be confirmed in current status: ' . $this->status);
        }

        try {
            $this->update([
                'status' => 'confirmed',
                'confirmed_by' => $confirmedBy,
                'confirmed_at' => now(),
            ]);

            // Update submission status if payment is confirmed
            if ($this->submission && $this->submission->status === 'payment_pending') {
                $this->submission->update(['status' => 'pending_approval']);
            }

            // Clear admin cache
            if ($confirmedBy) {
                Cache::forget('admin_pending_payments_' . $confirmedBy);
                Cache::forget('admin_integrated_stats_' . $confirmedBy);
            }

            // Create notification for user
            if (class_exists('\App\Models\Notification')) {
                try {
                    \App\Models\Notification::createPaymentNotification($this->id, 'payment_confirmed');
                } catch (\Exception $e) {
                    Log::warning('Failed to create payment confirmation notification', [
                        'payment_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Payment confirmed successfully', [
                'payment_id' => $this->id,
                'confirmed_by' => $confirmedBy,
                'submission_id' => $this->submission_id,
                'amount' => $this->amount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to confirm payment', [
                'payment_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Failed to confirm payment: ' . $e->getMessage());
        }
    }

    /**
     * ✅ ENHANCED: Reject payment - compatible dengan dashboard admin
     */
    public function reject($rejectedBy, $reason)
    {
        if (!$this->canBeRejected()) {
            throw new \Exception('Payment cannot be rejected in current status: ' . $this->status);
        }

        if (empty($reason)) {
            throw new \Exception('Rejection reason is required');
        }

        try {
            $this->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
            ]);

            // Clear admin cache
            if ($rejectedBy) {
                Cache::forget('admin_pending_payments_' . $rejectedBy);
                Cache::forget('admin_integrated_stats_' . $rejectedBy);
            }

            // Create notification for user
            if (class_exists('\App\Models\Notification')) {
                try {
                    \App\Models\Notification::createPaymentNotification($this->id, 'payment_rejected');
                } catch (\Exception $e) {
                    Log::warning('Failed to create payment rejection notification', [
                        'payment_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Payment rejected successfully', [
                'payment_id' => $this->id,
                'rejected_by' => $rejectedBy,
                'reason' => $reason,
                'submission_id' => $this->submission_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to reject payment', [
                'payment_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Failed to reject payment: ' . $e->getMessage());
        }
    }

    /**
     * ✅ ADDED: File management methods
     */
    public function deleteProof()
    {
        if ($this->payment_proof_path && Storage::disk('public')->exists($this->payment_proof_path)) {
            try {
                Storage::disk('public')->delete($this->payment_proof_path);
                Log::info('Payment proof deleted', [
                    'payment_id' => $this->id,
                    'file_path' => $this->payment_proof_path
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to delete payment proof', [
                    'payment_id' => $this->id,
                    'file_path' => $this->payment_proof_path,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function getProofFileSize()
    {
        if (!$this->payment_proof_path) {
            return 0;
        }

        if (Storage::disk('public')->exists($this->payment_proof_path)) {
            return Storage::disk('public')->size($this->payment_proof_path);
        }

        return 0;
    }

    public function hasProof()
    {
        return !empty($this->payment_proof_path) && 
               Storage::disk('public')->exists($this->payment_proof_path);
    }

    /**
     * ✅ ENHANCED: Statistics and reporting methods untuk dashboard admin
     */
    public static function getAdminStats()
    {
        try {
            return Cache::remember('payment_admin_stats', 300, function() {
                $pendingPayments = static::pending()->count();
                $confirmedPayments = static::confirmed()->count();
                $rejectedPayments = static::rejected()->count();
                $totalPayments = static::count();
                
                $totalRevenue = static::confirmed()->sum('amount') ?? 0;
                $pendingAmount = static::pending()->sum('amount') ?? 0;
                
                $todayPayments = static::today()->count();
                $todayRevenue = static::today()->confirmed()->sum('amount') ?? 0;
                
                $weeklyPayments = static::where('created_at', '>=', now()->startOfWeek())->count();
                $monthlyPayments = static::whereMonth('created_at', now()->month)->count();

                return [
                    'total_payments' => $totalPayments,
                    'pending_payments' => $pendingPayments,
                    'confirmed_payments' => $confirmedPayments,
                    'rejected_payments' => $rejectedPayments,
                    'total_revenue' => $totalRevenue,
                    'pending_amount' => $pendingAmount,
                    'today_payments' => $todayPayments,
                    'today_revenue' => $todayRevenue,
                    'weekly_payments' => $weeklyPayments,
                    'monthly_payments' => $monthlyPayments,
                    'average_amount' => $totalPayments > 0 ? $totalRevenue / $confirmedPayments : 0,
                    'pending_percentage' => $totalPayments > 0 ? round(($pendingPayments / $totalPayments) * 100, 1) : 0,
                    'confirmation_rate' => $totalPayments > 0 ? round(($confirmedPayments / $totalPayments) * 100, 1) : 0,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to get admin payment stats', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_payments' => 0,
                'pending_payments' => 0,
                'confirmed_payments' => 0,
                'rejected_payments' => 0,
                'total_revenue' => 0,
                'pending_amount' => 0,
                'today_payments' => 0,
                'today_revenue' => 0,
                'weekly_payments' => 0,
                'monthly_payments' => 0,
                'average_amount' => 0,
                'pending_percentage' => 0,
                'confirmation_rate' => 0,
            ];
        }
    }

    public static function getUserStats($userId)
    {
        return [
            'total_payments' => static::where('user_id', $userId)->count(),
            'confirmed_payments' => static::where('user_id', $userId)->confirmed()->count(),
            'pending_payments' => static::where('user_id', $userId)->pending()->count(),
            'rejected_payments' => static::where('user_id', $userId)->rejected()->count(),
            'total_spent' => static::where('user_id', $userId)->confirmed()->sum('amount') ?? 0,
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
     * ✅ ENHANCED: Bulk operations with better error handling untuk dashboard admin
     */
    public static function bulkConfirm(array $paymentIds, $adminId)
    {
        $confirmed = 0;
        $errors = [];
        
        Log::info('Starting bulk payment confirmation', [
            'payment_ids' => $paymentIds,
            'admin_id' => $adminId,
            'count' => count($paymentIds)
        ]);
        
        foreach ($paymentIds as $paymentId) {
            $payment = static::find($paymentId);
            
            if (!$payment) {
                $errors[] = "Payment ID {$paymentId} not found.";
                continue;
            }
            
            if (!$payment->canBeConfirmed()) {
                $errors[] = "Payment ID {$paymentId} cannot be confirmed (status: {$payment->status}).";
                continue;
            }
            
            try {
                $payment->confirm($adminId);
                $confirmed++;
            } catch (\Exception $e) {
                $errors[] = "Failed to confirm payment ID {$paymentId}: " . $e->getMessage();
                Log::error('Bulk confirm payment failed', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Clear admin cache
        Cache::forget('admin_pending_payments_' . $adminId);
        Cache::forget('admin_integrated_stats_' . $adminId);
        Cache::forget('payment_admin_stats');
        
        Log::info('Bulk payment confirmation completed', [
            'confirmed' => $confirmed,
            'errors_count' => count($errors),
            'admin_id' => $adminId
        ]);
        
        return [
            'confirmed' => $confirmed,
            'errors' => $errors
        ];
    }

    /**
     * ✅ ENHANCED: Bulk reject with better error handling untuk dashboard admin
     */
    public static function bulkReject(array $paymentIds, $reason, $adminId = null)
    {
        $rejected = 0;
        $errors = [];
        
        Log::info('Starting bulk payment rejection', [
            'payment_ids' => $paymentIds,
            'admin_id' => $adminId,
            'count' => count($paymentIds),
            'reason' => $reason
        ]);
        
        foreach ($paymentIds as $paymentId) {
            $payment = static::find($paymentId);
            
            if (!$payment) {
                $errors[] = "Payment ID {$paymentId} not found.";
                continue;
            }
            
            if (!$payment->canBeRejected()) {
                $errors[] = "Payment ID {$paymentId} cannot be rejected (status: {$payment->status}).";
                continue;
            }
            
            try {
                $payment->reject($adminId, $reason);
                $rejected++;
            } catch (\Exception $e) {
                $errors[] = "Failed to reject payment ID {$paymentId}: " . $e->getMessage();
                Log::error('Bulk reject payment failed', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Clear admin cache
        if ($adminId) {
            Cache::forget('admin_pending_payments_' . $adminId);
            Cache::forget('admin_integrated_stats_' . $adminId);
        }
        Cache::forget('payment_admin_stats');
        
        Log::info('Bulk payment rejection completed', [
            'rejected' => $rejected,
            'errors_count' => count($errors),
            'admin_id' => $adminId
        ]);
        
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
     * ✅ ADDED: Dashboard integration methods
     */
    public function getStatusBadgeClass()
    {
        $classes = [
            'pending' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            'confirmed' => 'bg-green-100 text-green-800 border border-green-200',
            'rejected' => 'bg-red-100 text-red-800 border border-red-200',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800 border border-gray-200';
    }

    public function getProgressPercentage()
    {
        $percentages = [
            'pending' => 50,
            'confirmed' => 100,
            'rejected' => 100,
        ];

        return $percentages[$this->status] ?? 0;
    }

    public function getTimelineData()
    {
        $timeline = [
            [
                'status' => 'created',
                'title' => 'Payment Created',
                'description' => 'Payment submitted by user',
                'timestamp' => $this->created_at,
                'completed' => true
            ]
        ];

        if ($this->isConfirmed()) {
            $timeline[] = [
                'status' => 'confirmed',
                'title' => 'Payment Confirmed',
                'description' => 'Payment confirmed by admin',
                'timestamp' => $this->confirmed_at,
                'completed' => true,
                'admin' => $this->confirmedBy->full_name ?? 'System'
            ];
        } elseif ($this->isRejected()) {
            $timeline[] = [
                'status' => 'rejected',
                'title' => 'Payment Rejected',
                'description' => $this->rejection_reason,
                'timestamp' => $this->rejected_at,
                'completed' => true,
                'admin' => $this->rejectedBy->full_name ?? 'System'
            ];
        }

        return $timeline;
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
            'category' => $this->submission->category->name ?? 'Unknown',
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'payment_method' => $this->payment_method_display,
            'status' => $this->status_display,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'confirmed_at' => $this->confirmed_at ? $this->confirmed_at->format('Y-m-d H:i:s') : null,
            'rejected_at' => $this->rejected_at ? $this->rejected_at->format('Y-m-d H:i:s') : null,
            'rejection_reason' => $this->rejection_reason,
            'confirmed_by' => $this->confirmedBy->full_name ?? null,
            'rejected_by' => $this->rejectedBy->full_name ?? null,
        ];
    }

    /**
     * ✅ ENHANCED: Model events dengan cleanup dan cache management
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($payment) {
            // Clear cache when new payment is created
            Cache::forget('payment_admin_stats');
            
            Log::info('Payment created', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'amount' => $payment->amount
            ]);
        });

        static::updated(function ($payment) {
            // Clear cache when payment is updated
            Cache::forget('payment_admin_stats');
            
            // Clear admin-specific cache if status changed
            if ($payment->isDirty('status')) {
                $adminUsers = \App\Models\User::where('role', 'admin')->pluck('id');
                foreach ($adminUsers as $adminId) {
                    Cache::forget('admin_pending_payments_' . $adminId);
                    Cache::forget('admin_integrated_stats_' . $adminId);
                }
            }
        });

        static::deleting(function ($payment) {
            // Delete payment proof file when payment is deleted
            $payment->deleteProof();
            
            // Clear cache
            Cache::forget('payment_admin_stats');
            
            Log::info('Payment deleted', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id
            ]);
        });
    }
}