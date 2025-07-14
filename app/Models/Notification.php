<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_user_id', 
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    
    /**
     * Penerima notifikasi
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pengirim notifikasi (user yang nge-like)
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * ✅ ADDED: Accessors for better UI display
     */
    public function getTypeIconAttribute()
    {
        $icons = [
            // Existing like/match system
            'like_received' => '💕',
            'match_created' => '🎉',
            
            // New submission system
            'submission_approved' => '✅',
            'submission_rejected' => '❌',
            'submission_published' => '📢',
            'submission_pending_approval' => '⏳',
            
            // Payment system
            'payment_confirmed' => '💰',
            'payment_rejected' => '❌',
            'payment_pending' => '⏳',
            
            // System notifications
            'system' => '🔔',
            'community' => '💬',
            'announcement' => '📋',
        ];

        return $icons[$this->type] ?? '📫';
    }

    public function getTypeColorAttribute()
    {
        $colors = [
            // Existing like/match system
            'like_received' => 'text-pink-600',
            'match_created' => 'text-purple-600',
            
            // New submission system
            'submission_approved' => 'text-green-600',
            'submission_rejected' => 'text-red-600',
            'submission_published' => 'text-purple-600',
            'submission_pending_approval' => 'text-blue-600',
            
            // Payment system
            'payment_confirmed' => 'text-green-600',
            'payment_rejected' => 'text-red-600',
            'payment_pending' => 'text-yellow-600',
            
            // System notifications
            'system' => 'text-gray-600',
            'community' => 'text-indigo-600',
            'announcement' => 'text-blue-600',
        ];

        return $colors[$this->type] ?? 'text-gray-600';
    }

    public function getTypeBadgeColorAttribute()
    {
        $colors = [
            // Existing like/match system
            'like_received' => 'bg-pink-100 text-pink-800',
            'match_created' => 'bg-purple-100 text-purple-800',
            
            // New submission system
            'submission_approved' => 'bg-green-100 text-green-800',
            'submission_rejected' => 'bg-red-100 text-red-800',
            'submission_published' => 'bg-purple-100 text-purple-800',
            'submission_pending_approval' => 'bg-blue-100 text-blue-800',
            
            // Payment system
            'payment_confirmed' => 'bg-green-100 text-green-800',
            'payment_rejected' => 'bg-red-100 text-red-800',
            'payment_pending' => 'bg-yellow-100 text-yellow-800',
            
            // System notifications
            'system' => 'bg-gray-100 text-gray-800',
            'community' => 'bg-indigo-100 text-indigo-800',
            'announcement' => 'bg-blue-100 text-blue-800',
        ];

        return $colors[$this->type] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * ✅ ADDED: Scopes for better filtering
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeFromUser($query, $fromUserId)
    {
        return $query->where('from_user_id', $fromUserId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSubmissionRelated($query)
    {
        return $query->whereIn('type', [
            'submission_approved',
            'submission_rejected', 
            'submission_published',
            'submission_pending_approval'
        ]);
    }

    public function scopePaymentRelated($query)
    {
        return $query->whereIn('type', [
            'payment_confirmed',
            'payment_rejected',
            'payment_pending'
        ]);
    }

    public function scopeLikeMatchRelated($query)
    {
        return $query->whereIn('type', [
            'like_received',
            'match_created'
        ]);
    }

    /**
     * Basic Actions
     */
    
    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return $this;
    }

    /**
     * ✅ ADDED: Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null
        ]);

        return $this;
    }

    /**
     * ✅ ADDED: Check if notification is related to submission
     */
    public function isSubmissionRelated()
    {
        return in_array($this->type, [
            'submission_approved',
            'submission_rejected',
            'submission_published', 
            'submission_pending_approval'
        ]);
    }

    /**
     * ✅ ADDED: Check if notification is related to payment
     */
    public function isPaymentRelated()
    {
        return in_array($this->type, [
            'payment_confirmed',
            'payment_rejected',
            'payment_pending'
        ]);
    }

    /**
     * ✅ ADDED: Check if notification is related to like/match system
     */
    public function isLikeMatchRelated()
    {
        return in_array($this->type, [
            'like_received',
            'match_created'
        ]);
    }

    /**
     * Static Helper Methods for Querying
     */
    
    /**
     * Get unread notifications for user
     */
    public static function unreadForUser($userId)
    {
        return static::where('user_id', $userId)
                    ->where('is_read', false)
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Count unread notifications for user
     */
    public static function unreadCountForUser($userId)
    {
        return static::where('user_id', $userId)
                    ->where('is_read', false)
                    ->count();
    }

    /**
     * ✅ ADDED: Get recent notifications for user
     */
    public static function recentForUser($userId, $limit = 10)
    {
        return static::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * ✅ ADDED: Mark all notifications as read for user
     */
    public static function markAllAsReadForUser($userId)
    {
        return static::where('user_id', $userId)
                    ->where('is_read', false)
                    ->update([
                        'is_read' => true,
                        'read_at' => now(),
                    ]);
    }

    /**
     * ✅ ADDED: General create notification method
     */
    public static function createForUser($userId, $title, $message, $type = 'system', $data = [], $fromUserId = null)
    {
        return static::create([
            'user_id' => $userId,
            'from_user_id' => $fromUserId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * EXISTING LIKE/MATCH SYSTEM METHODS (PRESERVED)
     */
    
    /**
     * Create like notification
     */
    public static function createLikeNotification($targetUserId, $fromUserId)
    {
        $fromUser = User::find($fromUserId);
        
        return static::create([
            'user_id' => $targetUserId,
            'from_user_id' => $fromUserId,
            'type' => 'like_received',
            'title' => $fromUser->full_name . ' menyukai Anda! 💕',
            'message' => 'Like balik untuk match dan mulai chat!',
            'data' => [
                'from_user' => [
                    'id' => $fromUser->id,
                    'name' => $fromUser->full_name,
                    'profile_picture' => $fromUser->profile_picture,
                    'prodi' => $fromUser->prodi
                ]
            ]
        ]);
    }

    /**
     * Create match notification for both users
     */
    public static function createMatchNotifications($user1Id, $user2Id, $matchId)
    {
        $user1 = User::find($user1Id);
        $user2 = User::find($user2Id);

        // Notification for user1
        static::create([
            'user_id' => $user1Id,
            'from_user_id' => $user2Id,
            'type' => 'match_created',
            'title' => 'IT\'S A MATCH! 🎉',
            'message' => 'Anda dan ' . $user2->full_name . ' saling menyukai!',
            'data' => [
                'match_id' => $matchId,
                'other_user' => [
                    'id' => $user2->id,
                    'name' => $user2->full_name,
                    'profile_picture' => $user2->profile_picture
                ]
            ]
        ]);

        // Notification for user2  
        static::create([
            'user_id' => $user2Id,
            'from_user_id' => $user1Id,
            'type' => 'match_created',
            'title' => 'IT\'S A MATCH! 🎉',
            'message' => 'Anda dan ' . $user1->full_name . ' saling menyukai!',
            'data' => [
                'match_id' => $matchId,
                'other_user' => [
                    'id' => $user1->id,
                    'name' => $user1->full_name,
                    'profile_picture' => $user1->profile_picture
                ]
            ]
        ]);
    }

    /**
     * ✅ NEW SUBMISSION SYSTEM METHODS
     */
    
    /**
     * Create submission notification
     */
    public static function createSubmissionNotification($submissionId, $type)
    {
        $submission = ContentSubmission::with(['user', 'category'])->find($submissionId);
        
        if (!$submission) {
            return null;
        }

        $notifications = [];
        
        switch ($type) {
            case 'submission_pending_approval':
                // Notify admins/moderators
                $admins = User::withModeratorPrivileges()->get();
                
                foreach ($admins as $admin) {
                    $notifications[] = static::create([
                        'user_id' => $admin->id,
                        'from_user_id' => $submission->user_id,
                        'type' => $type,
                        'title' => 'Submission Pending Approval',
                        'message' => "Submission '{$submission->title}' dari {$submission->user->full_name} menunggu persetujuan.",
                        'data' => [
                            'submission_id' => $submission->id,
                            'submission_title' => $submission->title,
                            'category' => $submission->category->name,
                            'user' => [
                                'id' => $submission->user->id,
                                'name' => $submission->user->full_name,
                            ]
                        ]
                    ]);
                }
                break;

            case 'submission_approved':
                $notifications[] = static::create([
                    'user_id' => $submission->user_id,
                    'type' => $type,
                    'title' => 'Konten Disetujui! ✅',
                    'message' => "Konten '{$submission->title}' telah disetujui oleh moderator.",
                    'data' => [
                        'submission_id' => $submission->id,
                        'submission_title' => $submission->title,
                        'category' => $submission->category->name,
                    ]
                ]);
                break;

            case 'submission_rejected':
                $notifications[] = static::create([
                    'user_id' => $submission->user_id,
                    'type' => $type,
                    'title' => 'Konten Ditolak ❌',
                    'message' => "Konten '{$submission->title}' ditolak. Silakan periksa dan edit ulang.",
                    'data' => [
                        'submission_id' => $submission->id,
                        'submission_title' => $submission->title,
                        'category' => $submission->category->name,
                        'rejection_reason' => $submission->rejection_reason,
                    ]
                ]);
                break;

            case 'submission_published':
                $notifications[] = static::create([
                    'user_id' => $submission->user_id,
                    'type' => $type,
                    'title' => 'Konten Dipublikasikan! 📢',
                    'message' => "Konten '{$submission->title}' telah dipublikasikan di komunitas {$submission->category->name}.",
                    'data' => [
                        'submission_id' => $submission->id,
                        'submission_title' => $submission->title,
                        'category' => $submission->category->name,
                    ]
                ]);
                break;
        }

        return $notifications;
    }

    /**
     * Create payment notification  
     */
    public static function createPaymentNotification($paymentId, $type)
    {
        $payment = Payment::with(['user', 'submission'])->find($paymentId);
        
        if (!$payment) {
            return null;
        }

        $notifications = [];
        
        switch ($type) {
            case 'payment_pending':
                // Notify admins about new payment to verify
                $admins = User::withModeratorPrivileges()->get();
                
                foreach ($admins as $admin) {
                    $notifications[] = static::create([
                        'user_id' => $admin->id,
                        'from_user_id' => $payment->user_id,
                        'type' => $type,
                        'title' => 'Pembayaran Baru Menunggu Konfirmasi',
                        'message' => "Pembayaran sebesar {$payment->formatted_amount} dari {$payment->user->full_name} menunggu konfirmasi.",
                        'data' => [
                            'payment_id' => $payment->id,
                            'amount' => $payment->amount,
                            'submission_id' => $payment->submission_id,
                            'submission_title' => $payment->submission->title ?? 'Unknown',
                            'user' => [
                                'id' => $payment->user->id,
                                'name' => $payment->user->full_name,
                            ]
                        ]
                    ]);
                }
                break;

            case 'payment_confirmed':
                $notifications[] = static::create([
                    'user_id' => $payment->user_id,
                    'type' => $type,
                    'title' => 'Pembayaran Dikonfirmasi! 💰',
                    'message' => "Pembayaran sebesar {$payment->formatted_amount} telah dikonfirmasi. Konten Anda sedang dalam proses review.",
                    'data' => [
                        'payment_id' => $payment->id,
                        'amount' => $payment->amount,
                        'submission_id' => $payment->submission_id,
                        'submission_title' => $payment->submission->title ?? 'Unknown',
                    ]
                ]);
                break;

            case 'payment_rejected':
                $notifications[] = static::create([
                    'user_id' => $payment->user_id,
                    'type' => $type,
                    'title' => 'Pembayaran Ditolak ❌',
                    'message' => "Pembayaran sebesar {$payment->formatted_amount} ditolak. Silakan upload ulang bukti pembayaran yang valid.",
                    'data' => [
                        'payment_id' => $payment->id,
                        'amount' => $payment->amount,
                        'submission_id' => $payment->submission_id,
                        'submission_title' => $payment->submission->title ?? 'Unknown',
                        'rejection_reason' => $payment->rejection_reason,
                    ]
                ]);
                break;
        }

        return $notifications;
    }

    /**
     * ✅ ADDED: Create system notification
     */
    public static function createSystemNotification($userId, $title, $message, $data = [])
    {
        return static::create([
            'user_id' => $userId,
            'type' => 'system',
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * ✅ ADDED: Broadcast notification to multiple users
     */
    public static function broadcastToUsers($userIds, $title, $message, $type = 'announcement', $data = [])
    {
        $notifications = [];
        
        foreach ($userIds as $userId) {
            $notifications[] = static::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        }
        
        return $notifications;
    }

    /**
     * ✅ ADDED: Broadcast to all admins
     */
    public static function broadcastToAdmins($title, $message, $type = 'system', $data = [])
    {
        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        
        return static::broadcastToUsers($adminIds, $title, $message, $type, $data);
    }

    /**
     * ✅ ADDED: Broadcast to all moderators
     */
    public static function broadcastToModerators($title, $message, $type = 'system', $data = [])
    {
        $moderatorIds = User::withModeratorPrivileges()->pluck('id')->toArray();
        
        return static::broadcastToUsers($moderatorIds, $title, $message, $type, $data);
    }

    /**
     * ✅ ADDED: Get notification statistics
     */
    public static function getStatsForUser($userId)
    {
        return [
            'total' => static::where('user_id', $userId)->count(),
            'unread' => static::where('user_id', $userId)->where('is_read', false)->count(),
            'submission_related' => static::where('user_id', $userId)->submissionRelated()->count(),
            'payment_related' => static::where('user_id', $userId)->paymentRelated()->count(),
            'like_match_related' => static::where('user_id', $userId)->likeMatchRelated()->count(),
            'today' => static::where('user_id', $userId)->whereDate('created_at', today())->count(),
        ];
    }

    /**
     * ✅ ADDED: Delete old read notifications
     */
    public static function cleanupOldNotifications($days = 30)
    {
        return static::where('is_read', true)
                    ->where('read_at', '<', now()->subDays($days))
                    ->delete();
    }

    /**
     * ✅ ADDED: Get all notification types
     */
    public static function getAllTypes()
    {
        return [
            // Like/Match system
            'like_received' => 'Like Received',
            'match_created' => 'Match Created',
            
            // Submission system
            'submission_approved' => 'Submission Approved',
            'submission_rejected' => 'Submission Rejected',
            'submission_published' => 'Submission Published',
            'submission_pending_approval' => 'Submission Pending Approval',
            
            // Payment system
            'payment_confirmed' => 'Payment Confirmed',
            'payment_rejected' => 'Payment Rejected',
            'payment_pending' => 'Payment Pending',
            
            // System
            'system' => 'System Notification',
            'community' => 'Community Notification',
            'announcement' => 'Announcement',
        ];
    }
}