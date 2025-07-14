<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ContentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'attachment_path',
        'attachment_type',
        'attachment_name',
        'attachment_size',
        'status',
        'payment_id',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(SubmissionCategory::class, 'category_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Accessors & Mutators
     */
    public function getAttachmentUrlAttribute()
    {
        if (!$this->attachment_path) {
            return null;
        }
        
        return asset('storage/' . $this->attachment_path);
    }

    public function getFormattedFileSizeAttribute()
    {
        if (!$this->attachment_size) return null;
        
        $bytes = $this->attachment_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStatusBadgeColorAttribute()
    {
        $colors = [
            'pending_payment' => 'bg-yellow-100 text-yellow-800',
            'pending_approval' => 'bg-blue-100 text-blue-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'published' => 'bg-purple-100 text-purple-800',
        ];

        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusDisplayAttribute()
    {
        $statuses = [
            'pending_payment' => 'Menunggu Pembayaran',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'published' => 'Dipublikasikan',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusIconAttribute()
    {
        $icons = [
            'pending_payment' => '💰',
            'pending_approval' => '⏳',
            'approved' => '✅',
            'rejected' => '❌',
            'published' => '📢',
        ];

        return $icons[$this->status] ?? '📄';
    }

    /**
     * Scopes
     */
    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Business Logic Methods
     */
    public function canBeEdited()
    {
        return in_array($this->status, ['pending_payment', 'rejected']);
    }

    /**
     * ✅ FIXED: Updated canBePaid() method with proper payment validation
     * Submission can be paid if:
     * - Status is pending_payment AND
     * - No payment exists OR existing payment was rejected
     */
    public function canBePaid()
    {
        return $this->status === 'pending_payment' && 
               (!$this->payment || $this->payment->status === 'rejected');
    }

    /**
     * ✅ NEW: Added canBeDeleted() method for payment system
     * Submission can be deleted if not yet approved or published
     */
    public function canBeDeleted()
    {
        return in_array($this->status, ['pending_payment', 'rejected']);
    }

    public function canBeApproved()
    {
        return $this->status === 'pending_approval';
    }

    public function canBePublished()
    {
        return $this->status === 'approved';
    }

    public function markAsPaid($paymentId)
    {
        $this->update([
            'status' => 'pending_approval',
            'payment_id' => $paymentId,
            'submitted_at' => now(),
        ]);

        // Create notification for admin (if Notification model exists)
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::createSubmissionNotification($this->id, 'submission_pending_approval');
        }
    }

    public function approve($adminId, $shouldPublish = false)
    {
        $status = $shouldPublish ? 'published' : 'approved';
        
        $this->update([
            'status' => $status,
            'approved_at' => now(),
            'approved_by' => $adminId,
            'rejection_reason' => null,
        ]);

        // Create notification for user (if Notification model exists)
        if (class_exists('\App\Models\Notification')) {
            $notificationType = $shouldPublish ? 'submission_published' : 'submission_approved';
            \App\Models\Notification::createSubmissionNotification($this->id, $notificationType);
        }

        // If published, create the actual group message
        if ($shouldPublish) {
            $this->publishToGroup();
        }
    }

    public function reject($adminId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'rejection_reason' => $reason,
        ]);

        // Create notification for user (if Notification model exists)
        if (class_exists('\App\Models\Notification')) {
            \App\Models\Notification::createSubmissionNotification($this->id, 'submission_rejected');
        }
    }

    public function publishToGroup()
    {
        if ($this->status !== 'approved' && $this->status !== 'published') {
            return false;
        }

        // Find the corresponding chat group
        $chatGroup = \App\Models\ChatGroup::where('name', 'LIKE', '%' . $this->category->name . '%')->first();
        
        if (!$chatGroup) {
            // Create group if doesn't exist
            $chatGroup = \App\Models\ChatGroup::create([
                'name' => $this->category->name,
                'description' => $this->category->description,
                'creator_id' => 1, // Admin user
                'is_approved' => true,
            ]);
        }

        // Create group message (if GroupMessage model exists)
        if (class_exists('\App\Models\GroupMessage')) {
            $message = \App\Models\GroupMessage::create([
                'group_id' => $chatGroup->id,
                'sender_id' => $this->user_id,
                'message_content' => "📢 {$this->title}\n\n{$this->description}",
                'attachment_path' => $this->attachment_path,
                'attachment_type' => $this->attachment_type,
                'attachment_name' => $this->attachment_name,
                'attachment_size' => $this->attachment_size,
            ]);
        }

        // Update status to published
        $this->update(['status' => 'published']);

        return $message ?? true;
    }

    public function deleteAttachment()
    {
        if ($this->attachment_path) {
            $storagePath = str_replace('storage/', 'public/', $this->attachment_path);
            
            if (Storage::exists($storagePath)) {
                Storage::delete($storagePath);
            }
            
            $this->update([
                'attachment_path' => null,
                'attachment_type' => null,
                'attachment_name' => null,
                'attachment_size' => null,
            ]);
            
            return true;
        }
        
        return false;
    }

    public function hasAttachment()
    {
        return !empty($this->attachment_path);
    }

    public function isImageAttachment()
    {
        return $this->attachment_type === 'image';
    }

    public function getAttachmentIconAttribute()
    {
        switch ($this->attachment_type) {
            case 'image':
                return 'fas fa-image';
            case 'pdf':
                return 'fas fa-file-pdf';
            case 'document':
                return 'fas fa-file-word';
            case 'spreadsheet':
                return 'fas fa-file-excel';
            default:
                return 'fas fa-file';
        }
    }

    public function getAttachmentColorAttribute()
    {
        switch ($this->attachment_type) {
            case 'image':
                return 'text-green-500';
            case 'pdf':
                return 'text-red-500';
            case 'document':
                return 'text-blue-500';
            case 'spreadsheet':
                return 'text-emerald-500';
            default:
                return 'text-gray-500';
        }
    }

    /**
     * Static methods
     */
    public static function getStatusOptions()
    {
        return [
            'pending_payment' => 'Menunggu Pembayaran',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'published' => 'Dipublikasikan',
        ];
    }

    public static function getUserSubmissionStats($userId)
    {
        return [
            'total' => static::where('user_id', $userId)->count(),
            'pending_payment' => static::where('user_id', $userId)->pendingPayment()->count(),
            'pending_approval' => static::where('user_id', $userId)->pendingApproval()->count(),
            'approved' => static::where('user_id', $userId)->approved()->count(),
            'rejected' => static::where('user_id', $userId)->rejected()->count(),
            'published' => static::where('user_id', $userId)->published()->count(),
        ];
    }

    public static function getAdminStats()
    {
        return [
            'total' => static::count(),
            'pending_approval' => static::pendingApproval()->count(),
            'approved' => static::approved()->count(),
            'rejected' => static::rejected()->count(),
            'published' => static::published()->count(),
            'revenue' => \App\Models\Payment::where('status', 'confirmed')->sum('amount'),
        ];
    }
}