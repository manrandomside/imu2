<?php
// ===== ChatGroup.php (Updated) =====

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    use HasFactory;

    protected $table = 'chat_groups';

    protected $fillable = [
        'name',
        'description',
        'creator_id',
        'moderator_id', // NEW: Moderator for this community
        'is_approved',
    ];

    /**
     * Creator of the group
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * NEW: Moderator of the group
     */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Members of the group
     */
    public function members()
    {
        return $this->hasMany(ChatGroupMember::class, 'group_id');
    }

    /**
     * Messages in the group
     */
    public function messages()
    {
        return $this->hasMany(GroupMessage::class, 'group_id');
    }

    /**
     * Check if user can post in this group
     */
    public function canUserPost(User $user)
    {
        // Admin global can post anywhere
        if ($user->role === 'admin') {
            return true;
        }

        // Group creator can post
        if ($this->creator_id === $user->id) {
            return true;
        }

        // NEW: Group moderator can post
        if ($this->moderator_id === $user->id) {
            return true;
        }

        return false;
    }
}