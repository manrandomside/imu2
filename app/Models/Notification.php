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
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

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
}