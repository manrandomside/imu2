<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password',
        'prodi',
        'fakultas',
        'gender',
        'description',
        'interests',
        'role',
        'profile_picture',
        'verification_doc_path',
        'is_verified',
        'match_categories',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'interests' => 'array',
            'is_verified' => 'boolean',
            'match_categories' => 'array',
        ];
    }

    // ===============================================
    // ✅ FIXED: ROLE MANAGEMENT METHODS untuk View Baru
    // ===============================================

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is moderator
     */
    public function isModerator()
    {
        return $this->role === 'moderator';
    }

    /**
     * Check if user is regular student/alumni/staff
     */
    public function isRegularUser()
    {
        return in_array($this->role, ['mahasiswa', 'alumni', 'tenaga_pendidik']);
    }

    /**
     * Check if user has admin or moderator privileges
     */
    public function hasModeratorPrivileges()
    {
        return in_array($this->role, ['admin', 'moderator']);
    }

    /**
     * ✅ ADDED: Additional role checks for submission system
     */
    public function isMahasiswa()
    {
        return $this->role === 'mahasiswa';
    }

    public function isAlumni()
    {
        return $this->role === 'alumni';
    }

    public function isDosen()
    {
        return $this->role === 'tenaga_pendidik';
    }

    /**
     * ✅ FIXED: Get user role display name (required by view)
     */
    public function getRoleDisplayAttribute()
    {
        $roleNames = [
            'admin' => 'Administrator',
            'moderator' => 'Moderator',
            'mahasiswa' => 'Mahasiswa', 
            'alumni' => 'Alumni',
            'tenaga_pendidik' => 'Tenaga Pendidik'
        ];

        return $roleNames[$this->role] ?? ucfirst($this->role);
    }

    /**
     * ✅ FIXED: Get user role badge color (required by view)
     */
    public function getRoleBadgeColorAttribute()
    {
        $colors = [
            'admin' => 'bg-red-100 text-red-800',
            'moderator' => 'bg-blue-100 text-blue-800',
            'mahasiswa' => 'bg-green-100 text-green-800',
            'alumni' => 'bg-purple-100 text-purple-800',
            'tenaga_pendidik' => 'bg-yellow-100 text-yellow-800'
        ];

        return $colors[$this->role] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * ✅ FIXED: Get user role icon (required by view)
     */
    public function getRoleIconAttribute()
    {
        $icons = [
            'admin' => '👑',
            'moderator' => '🛡️',
            'mahasiswa' => '🎓',
            'alumni' => '🏆',
            'tenaga_pendidik' => '👩‍🏫'
        ];

        return $icons[$this->role] ?? '👤';
    }

    // ===============================================
    // ✅ FIXED: COMMUNITY MODERATION RELATIONSHIPS
    // ===============================================

    /**
     * Communities where this user is moderator
     */
    public function moderatedCommunities()
    {
        return $this->hasMany(ChatGroup::class, 'moderator_id');
    }

    /**
     * Communities created by this user
     */
    public function createdCommunities()
    {
        return $this->hasMany(ChatGroup::class, 'creator_id');
    }

    /**
     * ✅ ADDED: SUBMISSION SYSTEM RELATIONSHIPS
     */
    public function submissions()
    {
        return $this->hasMany(ContentSubmission::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(GroupMessage::class, 'sender_id');
    }

    public function approvedSubmissions()
    {
        return $this->hasMany(ContentSubmission::class, 'approved_by');
    }

    public function confirmedPayments()
    {
        return $this->hasMany(Payment::class, 'confirmed_by');
    }

    /**
     * Get all communities this user can moderate (created + assigned as moderator)
     */
    public function getAllModeratedCommunities()
    {
        if ($this->isAdmin()) {
            // Admin can moderate all approved communities
            return ChatGroup::where('is_approved', true)->get();
        }

        // Get communities where user is creator OR moderator
        return ChatGroup::where(function($query) {
                $query->where('creator_id', $this->id)
                      ->orWhere('moderator_id', $this->id);
            })
            ->where('is_approved', true)
            ->get();
    }

    /**
     * Check if user can moderate specific community
     */
    public function canModerateCommunity($communityId)
    {
        if ($this->isAdmin()) {
            return true;
        }

        return ChatGroup::where('id', $communityId)
                       ->where(function($query) {
                           $query->where('creator_id', $this->id)
                                 ->orWhere('moderator_id', $this->id);
                       })
                       ->exists();
    }

    /**
     * Get communities where user can post
     */
    public function getPostableCommunities()
    {
        if ($this->isAdmin()) {
            // Admin can post to all approved communities
            return ChatGroup::where('is_approved', true)->get();
        }

        if ($this->isModerator()) {
            // Moderator can post to assigned communities
            return $this->getAllModeratedCommunities();
        }

        // Regular users cannot post to any community
        return collect();
    }

    // ===============================================
    // ✅ ADDED: SUBMISSION SYSTEM PERMISSION METHODS
    // ===============================================

    /**
     * Check if user can create submissions
     */
    public function canCreateSubmissions()
    {
        return $this->is_verified; // Only verified users can create submissions
    }

    /**
     * Check if user can manage submissions (approve/reject)
     */
    public function canManageSubmissions()
    {
        return $this->hasModeratorPrivileges();
    }

    /**
     * Check if user can manage payments (confirm/reject)
     */
    public function canManagePayments()
    {
        return $this->hasModeratorPrivileges();
    }

    /**
     * Check if user can moderate specific submission
     */
    public function canModerateSubmission($submission)
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Check if user owns the submission
        if ($submission->user_id === $this->id) {
            return true;
        }

        // Check if user is moderator for the submission's category community
        if ($this->isModerator() && $submission->category) {
            // This would need to be implemented based on your category-community relationship
            return true;
        }

        return false;
    }

    /**
     * Get submission statistics for this user
     */
    public function getSubmissionStats()
    {
        if (!$this->canCreateSubmissions()) {
            return null;
        }

        return [
            'total' => $this->submissions()->count(),
            'pending_payment' => $this->submissions()->where('status', 'pending_payment')->count(),
            'pending_approval' => $this->submissions()->where('status', 'pending_approval')->count(),
            'approved' => $this->submissions()->where('status', 'approved')->count(),
            'rejected' => $this->submissions()->where('status', 'rejected')->count(),
            'published' => $this->submissions()->where('status', 'published')->count(),
        ];
    }

    /**
     * Get payment statistics for this user
     */
    public function getPaymentStats()
    {
        return [
            'total_payments' => $this->payments()->count(),
            'pending_payments' => $this->payments()->where('status', 'pending')->count(),
            'confirmed_payments' => $this->payments()->where('status', 'confirmed')->count(),
            'rejected_payments' => $this->payments()->where('status', 'rejected')->count(),
            'total_spent' => $this->payments()->where('status', 'confirmed')->sum('amount'),
        ];
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCount()
    {
        return $this->notifications()->where('is_read', false)->count();
    }

    /**
     * Get user initials for avatar
     */
    public function getInitials()
    {
        $names = explode(' ', $this->full_name);
        $initials = '';
        
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        
        return $initials;
    }

    // ===============================================
    // ✅ SCOPE METHODS FOR FILTERING
    // ===============================================

    /**
     * Scope untuk admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope untuk moderator users
     */
    public function scopeModerators($query)
    {
        return $query->where('role', 'moderator');
    }

    /**
     * Scope untuk users dengan moderator privileges (admin + moderator)
     */
    public function scopeWithModeratorPrivileges($query)
    {
        return $query->whereIn('role', ['admin', 'moderator']);
    }

    /**
     * Scope untuk regular users (non-admin, non-moderator)
     */
    public function scopeRegularUsers($query)
    {
        return $query->whereIn('role', ['mahasiswa', 'alumni', 'tenaga_pendidik']);
    }

    /**
     * ✅ ADDED: Additional scopes for submission system
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByFakultas($query, $fakultas)
    {
        return $query->where('fakultas', $fakultas);
    }

    public function scopeByProdi($query, $prodi)
    {
        return $query->where('prodi', $prodi);
    }

    // ===============================================
    // ✅ STATIC HELPER METHODS
    // ===============================================

    /**
     * Get all available roles
     */
    public static function getAvailableRoles()
    {
        return [
            'mahasiswa' => 'Mahasiswa',
            'alumni' => 'Alumni', 
            'tenaga_pendidik' => 'Tenaga Pendidik',
            'moderator' => 'Moderator',
            'admin' => 'Administrator'
        ];
    }

    /**
     * Get moderator-eligible roles
     */
    public static function getModeratorEligibleRoles()
    {
        return ['moderator', 'admin'];
    }

    /**
     * Create moderator user
     */
    public static function createModerator($data)
    {
        return self::create(array_merge($data, [
            'role' => 'moderator',
            'is_verified' => true
        ]));
    }

    // ===============================================
    // ✅ VALIDATION HELPERS
    // ===============================================

    /**
     * Check if role transition is valid
     */
    public function canChangeRoleTo($newRole)
    {
        // Admin can change to any role
        if (auth()->user()?->isAdmin()) {
            return true;
        }

        // Regular users cannot change their own role
        if (auth()->id() === $this->id) {
            return false;
        }

        // Only admin can assign moderator role
        if ($newRole === 'moderator' && !auth()->user()?->isAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Get role upgrade path suggestions
     */
    public function getRoleUpgradeSuggestions()
    {
        $suggestions = [];

        if ($this->isRegularUser()) {
            $suggestions[] = [
                'role' => 'moderator',
                'reason' => 'Promosi ke moderator untuk mengelola komunitas tertentu'
            ];
        }

        if ($this->isModerator()) {
            $suggestions[] = [
                'role' => 'admin', 
                'reason' => 'Promosi ke admin untuk akses penuh sistem'
            ];
        }

        return $suggestions;
    }

    // ===============================================
    // ✅ EXISTING METHODS (MATCH CATEGORIES - DARI VERSI LAMA)
    // ===============================================

    /**
     * Safely get match categories as array
     */
    public function getMatchCategoriesAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (empty($value)) {
            return [];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }

    /**
     * Safely set match categories
     */
    public function setMatchCategoriesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['match_categories'] = json_encode($value);
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->attributes['match_categories'] = $value;
            } else {
                $this->attributes['match_categories'] = json_encode([]);
            }
        } else {
            $this->attributes['match_categories'] = json_encode([]);
        }
    }

    /**
     * Check if user has specific match category
     */
    public function hasMatchCategory($category)
    {
        $categories = $this->match_categories;
        return is_array($categories) && in_array($category, $categories);
    }

    /**
     * Check if user has any of the specified match categories
     */
    public function hasAnyMatchCategory(array $categories)
    {
        $userCategories = $this->match_categories;
        return is_array($userCategories) && !empty(array_intersect($userCategories, $categories));
    }

    /**
     * Get users with matching categories
     */
    public static function withMatchingCategories(array $categories)
    {
        return static::whereNotNull('match_categories')
                    ->get()
                    ->filter(function ($user) use ($categories) {
                        return $user->hasAnyMatchCategory($categories);
                    });
    }

    // ===============================================
    // ✅ INTERESTS METHODS (DARI VERSI LAMA)
    // ===============================================

    /**
     * Get interests as array
     */
    public function getInterestsAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (empty($value)) {
            return [];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }

    /**
     * Set interests as JSON
     */
    public function setInterestsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['interests'] = json_encode($value);
        } else {
            $this->attributes['interests'] = $value;
        }
    }

    /**
     * Check if user has specific interest
     */
    public function hasInterest($interest)
    {
        $interests = $this->interests;
        return is_array($interests) && in_array($interest, $interests);
    }

    // ===============================================
    // ✅ DEBUG METHODS (DARI VERSI LAMA) 
    // ===============================================

    /**
     * Debugging method: Get raw match_categories from database
     */
    public function getRawMatchCategories()
    {
        $raw = \DB::table('users')->where('id', $this->id)->value('match_categories');
        return [
            'raw_value' => $raw,
            'raw_type' => gettype($raw),
            'json_decode' => json_decode($raw, true),
            'cast_value' => $this->match_categories,
            'cast_type' => gettype($this->match_categories),
            'is_array' => is_array($this->match_categories)
        ];
    }

    /**
     * Method debug sederhana yang mudah digunakan di tinker
     */
    public function debugMatchCategories()
    {
        $raw = \DB::table('users')->where('id', $this->id)->value('match_categories');
        
        echo "========== DEBUG MATCH CATEGORIES ==========\n";
        echo "User ID: {$this->id}\n";
        echo "User Name: {$this->full_name}\n";
        echo "Email: {$this->email}\n";
        echo "\n--- RAW DATA FROM DATABASE ---\n";
        echo "Raw Value: " . ($raw ?? 'NULL') . "\n";
        echo "Raw Type: " . gettype($raw) . "\n";
        echo "\n--- VIA ELOQUENT MODEL ---\n";
        echo "Model Value: " . json_encode($this->match_categories) . "\n";
        echo "Model Type: " . gettype($this->match_categories) . "\n";
        echo "Is Array: " . (is_array($this->match_categories) ? 'YES' : 'NO') . "\n";
        echo "Is Null: " . (is_null($this->match_categories) ? 'YES' : 'NO') . "\n";
        echo "Is Empty: " . (empty($this->match_categories) ? 'YES' : 'NO') . "\n";
        echo "Count: " . (is_array($this->match_categories) ? count($this->match_categories) : 0) . "\n";
        
        // TAMBAHAN UNTUK INTERESTS
        echo "\n--- INTERESTS CHECK ---\n";
        $rawInterests = \DB::table('users')->where('id', $this->id)->value('interests');
        echo "Interests Raw: " . ($rawInterests ?? 'NULL') . "\n";
        echo "Interests Model: " . json_encode($this->interests) . "\n";
        echo "Interests Type: " . gettype($this->interests) . "\n";
        echo "Interests Is Array: " . (is_array($this->interests) ? 'YES' : 'NO') . "\n";
        
        echo "\n--- JSON TESTS ---\n";
        
        if ($raw) {
            $decoded = json_decode($raw, true);
            echo "JSON Decode Raw: " . json_encode($decoded) . "\n";
            echo "JSON Decode Success: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "\n";
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "JSON Error: " . json_last_error_msg() . "\n";
            }
        }
        
        echo "\n--- HELPER METHODS TEST ---\n";
        echo "hasMatchCategory('friends'): " . ($this->hasMatchCategory('friends') ? 'YES' : 'NO') . "\n";
        echo "hasMatchCategory('jobs'): " . ($this->hasMatchCategory('jobs') ? 'YES' : 'NO') . "\n";
        echo "hasAnyMatchCategory(['friends', 'jobs']): " . ($this->hasAnyMatchCategory(['friends', 'jobs']) ? 'YES' : 'NO') . "\n";
        echo "hasInterest('photography'): " . ($this->hasInterest('photography') ? 'YES' : 'NO') . "\n";
        
        // ✅ TAMBAHAN DEBUG UNTUK ROLE ATTRIBUTES
        echo "\n--- ROLE ATTRIBUTES CHECK ---\n";
        echo "Role: " . $this->role . "\n";
        echo "Role Display: " . $this->role_display . "\n";
        echo "Role Badge Color: " . $this->role_badge_color . "\n";
        echo "Role Icon: " . $this->role_icon . "\n";
        echo "Is Admin: " . ($this->isAdmin() ? 'YES' : 'NO') . "\n";
        echo "Is Moderator: " . ($this->isModerator() ? 'YES' : 'NO') . "\n";
        echo "Has Moderator Privileges: " . ($this->hasModeratorPrivileges() ? 'YES' : 'NO') . "\n";
        echo "Can Create Submissions: " . ($this->canCreateSubmissions() ? 'YES' : 'NO') . "\n";
        echo "Can Manage Submissions: " . ($this->canManageSubmissions() ? 'YES' : 'NO') . "\n";
        echo "Can Manage Payments: " . ($this->canManagePayments() ? 'YES' : 'NO') . "\n";
        echo "============================================\n\n";
        
        return $this;
    }

    // ===============================================
    // ✅ STATIC DEBUG METHODS (DARI VERSI LAMA)
    // ===============================================

    public static function debugAllUsers()
    {
        echo "========== DEBUG ALL USERS ==========\n";
        
        $totalUsers = static::count();
        echo "Total Users: {$totalUsers}\n";
        
        $usersWithCategories = static::whereNotNull('match_categories')->count();
        echo "Users with match_categories: {$usersWithCategories}\n\n";
        
        $users = static::whereNotNull('match_categories')->take(5)->get();
        
        if ($users->count() === 0) {
            echo "No users found with match_categories\n";
            echo "=====================================\n\n";
            return;
        }
        
        foreach ($users as $user) {
            echo "User {$user->id} ({$user->full_name}):\n";
            echo "  Role: {$user->role}\n";
            echo "  Categories: " . json_encode($user->match_categories) . "\n";
            echo "  Is Array: " . (is_array($user->match_categories) ? 'YES' : 'NO') . "\n";
            echo "  Count: " . (is_array($user->match_categories) ? count($user->match_categories) : 0) . "\n";
            echo "  Role Display: " . $user->role_display . "\n";
            echo "  Can Create Submissions: " . ($user->canCreateSubmissions() ? 'YES' : 'NO') . "\n\n";
        }
        
        echo "=====================================\n\n";
    }

    public function testUpdateMatchCategories($categories = ['friends', 'jobs'])
    {
        echo "========== TEST UPDATE MATCH CATEGORIES ==========\n";
        echo "User: {$this->full_name} (ID: {$this->id})\n";
        echo "Old Categories: " . json_encode($this->match_categories) . "\n";
        echo "New Categories: " . json_encode($categories) . "\n";
        
        try {
            $this->match_categories = $categories;
            $result = $this->save();
            
            echo "Save Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
            
            if ($result) {
                $fresh = $this->fresh();
                echo "After Save: " . json_encode($fresh->match_categories) . "\n";
                
                $rawAfter = \DB::table('users')->where('id', $this->id)->value('match_categories');
                echo "Raw After Save: " . ($rawAfter ?? 'NULL') . "\n";
            }
            
        } catch (\Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
        
        echo "================================================\n\n";
        
        return $this;
    }

    public static function createTestUser()
    {
        echo "========== CREATING TEST USER ==========\n";
        
        try {
            $existingUser = static::where('email', 'testdebug@student.unud.ac.id')->first();
            
            if ($existingUser) {
                echo "Test user already exists (ID: {$existingUser->id})\n";
                echo "Updating existing user...\n";
                
                $existingUser->match_categories = ['friends', 'jobs', 'pkm'];
                $result = $existingUser->save();
                
                echo "Update Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
                echo "==========================================\n\n";
                
                return $existingUser;
            }
            
            $user = static::create([
                'full_name' => 'Test Debug User',
                'username' => 'testdebug' . time(),
                'email' => 'testdebug@student.unud.ac.id',
                'password' => \Hash::make('password'),
                'role' => 'mahasiswa',
                'is_verified' => true,
                'prodi' => 'Teknik Informatika',
                'fakultas' => 'Teknik',
                'gender' => 'Laki-laki',
                'description' => 'Test user untuk debugging match categories',
                'interests' => ['programming', 'debugging'],
                'match_categories' => ['friends', 'jobs', 'test']
            ]);
            
            echo "Test user created successfully!\n";
            echo "ID: {$user->id}\n";
            echo "Name: {$user->full_name}\n";
            echo "Categories: " . json_encode($user->match_categories) . "\n";
            echo "==========================================\n\n";
            
            return $user;
            
        } catch (\Exception $e) {
            echo "ERROR creating test user: " . $e->getMessage() . "\n";
            echo "==========================================\n\n";
            return null;
        }
    }

    public static function testQueryFiltering($testCategories = ['friends', 'jobs'])
    {
        echo "========== TEST QUERY FILTERING ==========\n";
        echo "Testing with categories: " . json_encode($testCategories) . "\n\n";
        
        echo "--- Method 1: whereJsonContains ---\n";
        try {
            $count1 = 0;
            foreach ($testCategories as $category) {
                $users = static::whereJsonContains('match_categories', $category)->get();
                $count1 += $users->count();
                echo "Category '{$category}': {$users->count()} users\n";
            }
            echo "Total unique users: Requires deduplication\n";
            echo "Method 1 Status: SUPPORTED\n\n";
        } catch (\Exception $e) {
            echo "Method 1 Status: NOT SUPPORTED - " . $e->getMessage() . "\n\n";
        }
        
        echo "--- Method 2: Collection Filtering ---\n";
        $allUsers = static::whereNotNull('match_categories')->get();
        $matchedUsers = $allUsers->filter(function ($user) use ($testCategories) {
            return $user->hasAnyMatchCategory($testCategories);
        });
        echo "Total users with categories: {$allUsers->count()}\n";
        echo "Matched users: {$matchedUsers->count()}\n";
        echo "Method 2 Status: ALWAYS WORKS\n\n";
        
        if ($matchedUsers->count() > 0) {
            echo "--- Matched Users ---\n";
            foreach ($matchedUsers->take(3) as $user) {
                echo "User {$user->id} ({$user->full_name}): " . json_encode($user->match_categories) . "\n";
            }
        }
        
        echo "=========================================\n\n";
        
        return $matchedUsers;
    }
}