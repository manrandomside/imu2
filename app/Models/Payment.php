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

    public function confirm($adminId = null)
    {
        if (!$this->canBeConfirmed()) {
            throw new \Exception('Payment cannot be confirmed');
        }

        $this->update([
            'status' => 'confirmed',
            'confirmed_by' => $adminId,
            'confirmed_at' => now(),
        ]);

        // Update submission status if payment is confirmed
        if ($this->submission) {
            $this->submission->update(['status' => 'pending_approval']);
        }

        // Create notification for user
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::createPaymentNotification($this->id, 'payment_confirmed');
        }

        return $this;
    }

    public function reject($reason, $adminId = null)
    {
        if (!$this->canBeRejected()) {
            throw new \Exception('Payment cannot be rejected');
        }

        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'confirmed_by' => $adminId,
            'confirmed_at' => now(),
        ]);

        // Create notification for user
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::createPaymentNotification($this->id, 'payment_rejected');
        }

        return $this;
    }

    /**
     * ✅ ADDED: File management methods
     */
    public function deleteProof()
    {
        if ($this->payment_proof_path && Storage::disk('public')->exists($this->payment_proof_path)) {
            Storage::disk('public')->delete($this->payment_proof_path);
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
     * ✅ ADDED: Statistics and reporting methods
     */
    public static function getAdminStats()
    {
        return [
            'total' => static::count(),
            'pending' => static::pending()->count(),
            'confirmed' => static::confirmed()->count(),
            'rejected' => static::rejected()->count(),
            'total_revenue' => static::confirmed()->sum('amount') ?? 0,
            'pending_amount' => static::pending()->sum('amount') ?? 0,
            'today' => static::today()->count(),
            'this_week' => static::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => static::whereMonth('created_at', now()->month)->count(),
        ];
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
            'category' => $this->submission->category->name ?? 'Unknown',
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'payment_method' => $this->payment_method_display,
            'status' => $this->status_display,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'confirmed_at' => $this->confirmed_at ? $this->confirmed_at->format('Y-m-d H:i:s') : null,
            'rejection_reason' => $this->rejection_reason,
        ];
    }

    /**
     * ✅ ADDED: Delete method with cleanup
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($payment) {
            // Delete payment proof file when payment is deleted
            $payment->deleteProof();
        });
    }
}