<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

        return $methods[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
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
     * Business Logic Methods
     */
    public function canBeConfirmed()
    {
        return $this->status === 'pending';
    }

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
        Notification::createPaymentNotification($this->id, 'payment_confirmed');
    }

    public function reject($adminId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'confirmed_by' => $adminId,
            'rejection_reason' => $reason,
        ]);

        // Create notification for user
        Notification::createPaymentNotification($this->id, 'payment_rejected');
    }

    public function deleteProof()
    {
        if ($this->payment_proof_path) {
            $storagePath = str_replace('storage/', 'public/', $this->payment_proof_path);
            
            if (Storage::exists($storagePath)) {
                Storage::delete($storagePath);
            }
            
            $this->update(['payment_proof_path' => null]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Static methods
     */
    public static function getPaymentMethods()
    {
        return [
            'transfer_bank' => [
                'name' => 'Transfer Bank',
                'icon' => 'fas fa-university',
                'details' => 'Bank BCA - 1234567890 - a.n. Admin IMU'
            ],
            'dana' => [
                'name' => 'DANA',
                'icon' => 'fas fa-mobile-alt',
                'details' => '081234567890'
            ],
            'gopay' => [
                'name' => 'GoPay',
                'icon' => 'fas fa-mobile-alt',
                'details' => '081234567890'
            ],
            'ovo' => [
                'name' => 'OVO',
                'icon' => 'fas fa-mobile-alt',
                'details' => '081234567890'
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

    public static function getRevenue($startDate = null, $endDate = null)
    {
        $query = static::confirmed();
        
        if ($startDate) {
            $query->whereDate('confirmed_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('confirmed_at', '<=', $endDate);
        }
        
        return $query->sum('amount');
    }

    public static function getAdminStats()
    {
        return [
            'total_payments' => static::count(),
            'pending_payments' => static::pending()->count(),
            'confirmed_payments' => static::confirmed()->count(),
            'rejected_payments' => static::rejected()->count(),
            'total_revenue' => static::getRevenue(),
            'revenue_this_month' => static::getRevenue(now()->startOfMonth(), now()->endOfMonth()),
        ];
    }
}