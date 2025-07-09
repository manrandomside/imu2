<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    use HasFactory;

    protected $table = 'group_messages';

    protected $fillable = [
        'group_id',
        'sender_id',
        'message_content',
        'attachment_path',    // NEW: File attachment
        'attachment_type',    // NEW: File type
        'attachment_name',    // NEW: Original filename
        'attachment_size',    // NEW: File size
    ];

    /**
     * Group that owns this message
     */
    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    /**
     * User who sent this message
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * NEW: Reactions to this message
     */
    public function reactions()
    {
        return $this->hasMany(PostReaction::class, 'message_id');
    }

    /**
     * NEW: Comments on this message
     */
    public function comments()
    {
        return $this->hasMany(PostComment::class, 'message_id');
    }

    /**
     * NEW: Get reaction counts
     */
    public function getReactionCounts()
    {
        return $this->reactions()
                   ->selectRaw('reaction_type, count(*) as count')
                   ->groupBy('reaction_type')
                   ->pluck('count', 'reaction_type')
                   ->toArray();
    }

    /**
     * NEW: Check if user has reacted
     */
    public function hasUserReacted($userId, $reactionType = 'like')
    {
        return $this->reactions()
                   ->where('user_id', $userId)
                   ->where('reaction_type', $reactionType)
                   ->exists();
    }

    /**
     * NEW: Get attachment URL
     */
    public function getAttachmentUrlAttribute()
    {
        return $this->attachment_path ? asset($this->attachment_path) : null;
    }

    /**
     * NEW: Check if message has attachment
     */
    public function hasAttachment()
    {
        return !empty($this->attachment_path);
    }

    /**
     * NEW: Get formatted file size
     */
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
}
