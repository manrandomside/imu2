<?php

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
        'moderator_id',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    /**
     * Creator of the group
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Moderator of the group
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
     * ✅ ENHANCED: Check if user can post in this group dengan detailed logic
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

        // Group moderator can post
        if ($this->moderator_id === $user->id) {
            return true;
        }

        // Regular users cannot post
        return false;
    }

    /**
     * ✅ NEW: Check if user can moderate this group
     */
    public function canUserModerate(User $user)
    {
        // Admin global can moderate anywhere
        if ($user->role === 'admin') {
            return true;
        }

        // Group creator can moderate
        if ($this->creator_id === $user->id) {
            return true;
        }

        // Group moderator can moderate
        if ($this->moderator_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * ✅ NEW: Check if user can read this group
     */
    public function canUserRead(User $user)
    {
        // All authenticated users can read approved groups
        return $this->is_approved;
    }

    /**
     * ✅ NEW: Get posting permissions untuk user
     */
    public function getUserPermissions(User $user)
    {
        return [
            'can_read' => $this->canUserRead($user),
            'can_post' => $this->canUserPost($user),
            'can_moderate' => $this->canUserModerate($user),
            'can_edit_messages' => $this->canUserModerate($user),
            'can_delete_messages' => $this->canUserModerate($user),
            'user_role_in_group' => $this->getUserRoleInGroup($user),
        ];
    }

    /**
     * ✅ NEW: Get user role dalam group
     */
    public function getUserRoleInGroup(User $user)
    {
        if ($user->role === 'admin') {
            return 'admin';
        }
        
        if ($this->creator_id === $user->id) {
            return 'creator';
        }
        
        if ($this->moderator_id === $user->id) {
            return 'moderator';
        }
        
        return 'member';
    }

    /**
     * ✅ NEW: Get recent messages dengan eager loading
     */
    public function getRecentMessages($limit = 50)
    {
        return $this->messages()
                   ->with([
                       'sender:id,full_name,role,profile_picture',
                       'reactions' => function($query) {
                           $query->with('user:id,full_name');
                       },
                       'comments' => function($query) {
                           $query->with('user:id,full_name,profile_picture')
                                 ->whereNull('parent_id') // Only top-level
                                 ->orderBy('created_at', 'asc')
                                 ->limit(3);
                       }
                   ])
                   ->orderBy('created_at', 'desc')
                   ->limit($limit)
                   ->get()
                   ->reverse();
    }

    /**
     * ✅ NEW: Get group statistics
     */
    public function getStatsAttribute()
    {
        return [
            'total_messages' => $this->messages()->count(),
            'total_members' => $this->members()->count(),
            'messages_today' => $this->messages()->whereDate('created_at', today())->count(),
            'messages_this_week' => $this->messages()->where('created_at', '>=', now()->subWeek())->count(),
            'last_activity' => $this->messages()->latest()->first()?->created_at,
            'top_contributors' => $this->getTopContributors(),
        ];
    }

    /**
     * ✅ NEW: Get top contributors dalam group
     */
    public function getTopContributors($limit = 5)
    {
        return $this->messages()
                   ->select('sender_id', \DB::raw('count(*) as message_count'))
                   ->with('sender:id,full_name,profile_picture')
                   ->groupBy('sender_id')
                   ->orderBy('message_count', 'desc')
                   ->limit($limit)
                   ->get()
                   ->map(function($item) {
                       return [
                           'user' => $item->sender,
                           'message_count' => $item->message_count,
                       ];
                   });
    }

    /**
     * ✅ NEW: Scope untuk approved groups
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * ✅ NEW: Scope untuk groups dengan moderator tertentu
     */
    public function scopeModeratedBy($query, $userId)
    {
        return $query->where('moderator_id', $userId);
    }

    /**
     * ✅ NEW: Scope untuk groups yang dibuat oleh user tertentu
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('creator_id', $userId);
    }

    /**
     * ✅ NEW: Get group icon/emoji berdasarkan nama
     */
    public function getIconAttribute()
    {
        $icons = [
            'Lomba' => '🏆',
            'Workshop' => '🛠️',
            'Seminar' => '🎤',
            'Info Beasiswa' => '🎓',
            'PKM' => '🔬',
            'Lowongan Kerja' => '💼',
            'Pengumuman Umum' => '📢',
            'Info Akademik' => '📚',
        ];

        return $icons[$this->name] ?? '💬';
    }

    /**
     * ✅ NEW: Get group color berdasarkan nama
     */
    public function getColorClassAttribute()
    {
        $colors = [
            'Lomba' => 'bg-yellow-500',
            'Workshop' => 'bg-blue-500',
            'Seminar' => 'bg-purple-500',
            'Info Beasiswa' => 'bg-green-500',
            'PKM' => 'bg-indigo-500',
            'Lowongan Kerja' => 'bg-gray-500',
            'Pengumuman Umum' => 'bg-red-500',
            'Info Akademik' => 'bg-teal-500',
        ];

        return $colors[$this->name] ?? 'bg-blue-500';
    }

    /**
     * ✅ NEW: Check if group is active (has recent messages)
     */
    public function isActive()
    {
        $lastMessage = $this->messages()->latest()->first();
        
        if (!$lastMessage) {
            return false;
        }
        
        return $lastMessage->created_at->greaterThan(now()->subDays(7));
    }

    /**
     * ✅ NEW: Get activity level
     */
    public function getActivityLevelAttribute()
    {
        $messagesThisWeek = $this->messages()->where('created_at', '>=', now()->subWeek())->count();
        
        if ($messagesThisWeek >= 20) {
            return 'high';
        } elseif ($messagesThisWeek >= 5) {
            return 'medium';
        } elseif ($messagesThisWeek >= 1) {
            return 'low';
        } else {
            return 'inactive';
        }
    }

    /**
     * ✅ NEW: Get engagement score
     */
    public function getEngagementScoreAttribute()
    {
        $totalMessages = $this->messages()->count();
        $totalReactions = \DB::table('post_reactions')
                            ->whereIn('message_id', $this->messages()->pluck('id'))
                            ->count();
        $totalComments = \DB::table('post_comments')
                           ->whereIn('message_id', $this->messages()->pluck('id'))
                           ->count();
        
        return $totalMessages + ($totalReactions * 0.5) + ($totalComments * 1.5);
    }

    /**
     * ✅ NEW: Auto-assign moderator berdasarkan activity
     */
    public function assignAutoModerator()
    {
        if ($this->moderator_id) {
            return false; // Already has moderator
        }

        $topContributor = $this->getTopContributors(1)->first();
        
        if ($topContributor && $topContributor['message_count'] >= 10) {
            $this->update(['moderator_id' => $topContributor['user']['id']]);
            return true;
        }
        
        return false;
    }

    /**
     * ✅ NEW: Validate group name uniqueness
     */
    public static function isNameAvailable($name, $excludeId = null)
    {
        $query = static::where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }
}