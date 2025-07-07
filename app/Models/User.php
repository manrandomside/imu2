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

    // =====================================================
    // ACCESSOR & MUTATOR UNTUK INTERESTS (BARU - MINIMAL)
    // =====================================================

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

    // =====================================================
    // HELPER METHODS UNTUK INTERESTS (BARU - MINIMAL)
    // =====================================================

    /**
     * Check if user has specific interest
     */
    public function hasInterest($interest)
    {
        $interests = $this->interests;
        return is_array($interests) && in_array($interest, $interests);
    }

    // =====================================================
    // METHOD DEBUG YANG SUDAH ADA (TIDAK BERUBAH)
    // =====================================================

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
        echo "============================================\n\n";
        
        return $this;
    }

    // =====================================================
    // SEMUA METHOD LAINNYA TETAP SAMA SEPERTI SEBELUMNYA
    // =====================================================

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
            echo "  Categories: " . json_encode($user->match_categories) . "\n";
            echo "  Is Array: " . (is_array($user->match_categories) ? 'YES' : 'NO') . "\n";
            echo "  Count: " . (is_array($user->match_categories) ? count($user->match_categories) : 0) . "\n\n";
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
}