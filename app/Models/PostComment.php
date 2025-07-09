<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'user_id',
        'comment_content',
        'parent_id',
    ];

    /**
     * Message that was commented on
     */
    public function message()
    {
        return $this->belongsTo(GroupMessage::class, 'message_id');
    }

    /**
     * User who made the comment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent comment (for nested replies)
     */
    public function parent()
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    /**
     * Child comments (replies to this comment)
     */
    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_id');
    }

    /**
     * Check if this is a reply to another comment
     */
    public function isReply()
    {
        return !is_null($this->parent_id);
    }
}
