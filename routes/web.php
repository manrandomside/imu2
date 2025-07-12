<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\NotificationController;

// Rute default, bisa diarahkan ke halaman login atau register nantinya
Route::get('/', function () {
    return view('welcome');
});

// ===================================================================
// AUTHENTICATION ROUTES (No Middleware)
// ===================================================================

// Rute Registrasi Mahasiswa
Route::get('/register/student', [AuthController::class, 'showRegisterStudentForm'])->name('register.student');
Route::post('/register/student', [AuthController::class, 'registerStudent'])->name('register.store.student');

// Rute Registrasi Alumni
Route::get('/register/alumni', [AuthController::class, 'showRegisterAlumniForm'])->name('register.alumni');
Route::post('/register/alumni', [AuthController::class, 'registerAlumni'])->name('register.store.alumni');

// Rute Login
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

// Rute Logout
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Rute Halaman Notifikasi Verifikasi Alumni
Route::get('/alumni/verification-pending', [AuthController::class, 'showAlumniVerificationPendingPage'])->name('alumni.verification.pending');

// ===================================================================
// PROFILE SETUP ROUTES (Auth Required, Profile Incomplete Allowed)
// ===================================================================

Route::middleware('auth')->group(function () {
    Route::get('/profile/setup', [AuthController::class, 'showProfileSetupForm'])->name('profile.setup');
    Route::post('/profile/store', [ProfileController::class, 'storeBasicProfile'])->name('profile.store');
    Route::post('/match/store-categories', [ProfileController::class, 'storeMatchCategories'])->name('profile.store_match_categories');
});

// ===================================================================
// MAIN APPLICATION ROUTES (Auth Required)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // ✅ CORE APPLICATION PAGES
    Route::get('/home', [AuthController::class, 'showHomePage'])->name('home');
    Route::get('/match/setup', [AuthController::class, 'showMatchSetupForm'])->name('match.setup');
    Route::get('/find-people', [AuthController::class, 'showFindingPeoplePage'])->name('find.people');
    Route::get('/profile', [AuthController::class, 'showUserProfilePage'])->name('user.profile');
    
    // ✅ USER INTERACTIONS
    Route::post('/user/interact', [ProfileController::class, 'storeInteraction'])->name('user.interact');
    
    // ✅ PERSONAL CHAT ROUTES
    Route::get('/chat/personal', [ChatController::class, 'showPersonalChatPage'])->name('chat.personal');
    Route::post('/chat/send-message', [ChatController::class, 'sendMessage'])->name('chat.send_message');
    Route::get('/chat/messages', [ChatController::class, 'getMessages'])->name('chat.get_messages');
    
    // ✅ NOTIFICATION ROUTES
    Route::get('/notifications/count', [NotificationController::class, 'getUnreadCount'])->name('notifications.count');
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.api');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all_read');
    Route::post('/notifications/like-back', [NotificationController::class, 'likeBack'])->name('notifications.like_back');
    Route::get('/notifications/page', [NotificationController::class, 'index'])->name('notifications.index');
});

// ===================================================================
// ✅ ENHANCED COMMUNITY ROUTES (Complete Functionality)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // ✅ BASIC COMMUNITY ROUTES
    Route::get('/community', [CommunityController::class, 'showCommunityChatPage'])->name('community');
    Route::post('/community/send-message', [CommunityController::class, 'sendGroupMessage'])->name('community.send_message');
    Route::get('/community/messages', [CommunityController::class, 'getMessages'])->name('community.get_messages');
    Route::get('/community/stats', [CommunityController::class, 'getStats'])->name('community.stats');
    
    // ✅ REACTIONS SYSTEM ROUTES
    Route::post('/community/reactions', [CommunityController::class, 'addReaction'])->name('community.add_reaction');
    Route::delete('/community/reactions/{messageId}', [CommunityController::class, 'removeReaction'])->name('community.remove_reaction');
    Route::get('/community/reactions/{messageId}', [CommunityController::class, 'getReactions'])->name('community.get_reactions');
    
    // ✅ COMMENTS SYSTEM ROUTES  
    Route::post('/community/comments', [CommunityController::class, 'addComment'])->name('community.add_comment');
    Route::get('/community/comments', [CommunityController::class, 'loadComments'])->name('community.load_comments');
    Route::delete('/community/comments/{commentId}', [CommunityController::class, 'deleteComment'])->name('community.delete_comment');
    Route::put('/community/comments/{commentId}', [CommunityController::class, 'editComment'])->name('community.edit_comment');
    
    // ✅ PERMISSION SYSTEM ROUTES
    Route::get('/community/permissions', [CommunityController::class, 'getUserGroupPermissions'])->name('community.get_permissions');
    
    // ✅ FILE MANAGEMENT ROUTES
    Route::delete('/community/attachments/{messageId}', [CommunityController::class, 'deleteAttachment'])->name('community.delete_attachment');
    Route::get('/community/attachments/{messageId}/download', [CommunityController::class, 'downloadAttachment'])->name('community.download_attachment');
    
    // ✅ MESSAGE MANAGEMENT ROUTES (for moderators/admins)
    Route::delete('/community/messages/{messageId}', [CommunityController::class, 'deleteMessage'])->name('community.delete_message');
    Route::put('/community/messages/{messageId}', [CommunityController::class, 'editMessage'])->name('community.edit_message');
    
    // ✅ GROUP MANAGEMENT ROUTES (for admins)
    Route::get('/community/groups', [CommunityController::class, 'getGroups'])->name('community.get_groups');
    Route::post('/community/groups', [CommunityController::class, 'createGroup'])->name('community.create_group');
    Route::put('/community/groups/{groupId}', [CommunityController::class, 'updateGroup'])->name('community.update_group');
    Route::delete('/community/groups/{groupId}', [CommunityController::class, 'deleteGroup'])->name('community.delete_group');
    Route::post('/community/groups/{groupId}/approve', [CommunityController::class, 'approveGroup'])->name('community.approve_group');
    Route::post('/community/groups/{groupId}/assign-moderator', [CommunityController::class, 'assignModerator'])->name('community.assign_moderator');
    
    // ✅ ADDITIONAL COMMUNITY API ROUTES
    Route::get('/community/search', [CommunityController::class, 'searchMessages'])->name('community.search_messages');
    Route::get('/community/trending', [CommunityController::class, 'getTrendingMessages'])->name('community.trending_messages');
    Route::post('/community/report', [CommunityController::class, 'reportMessage'])->name('community.report_message');
});

// ===================================================================
// DEBUG ROUTES (Development Only)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // ✅ MATCH CATEGORIES DEBUG
    Route::get('/debug/match-categories', function() {
        if (!app()->environment('local')) {
            abort(403, 'Only available in local environment');
        }
        
        $output = [];
        
        // 1. Cek current user (jika login)
        if (Auth::check()) {
            $currentUser = Auth::user();
            $output['current_user'] = [
                'id' => $currentUser->id,
                'name' => $currentUser->full_name,
                'raw_match_categories' => DB::table('users')->where('id', $currentUser->id)->value('match_categories'),
                'model_match_categories' => $currentUser->match_categories,
                'is_array' => is_array($currentUser->match_categories),
                'count' => is_array($currentUser->match_categories) ? count($currentUser->match_categories) : 0
            ];
        } else {
            $output['current_user'] = 'Not logged in';
        }
        
        // 2. Cek semua users dengan match_categories
        $usersWithCategories = App\Models\User::whereNotNull('match_categories')->get();
        $output['all_users_with_categories'] = [];
        
        foreach ($usersWithCategories as $user) {
            $output['all_users_with_categories'][] = [
                'id' => $user->id,
                'name' => $user->full_name,
                'raw' => DB::table('users')->where('id', $user->id)->value('match_categories'),
                'model' => $user->match_categories,
                'is_array' => is_array($user->match_categories)
            ];
        }
        
        // 3. Test database connection dan structure
        $output['database_info'] = [
            'connection' => config('database.default'),
            'match_categories_column_exists' => Schema::hasColumn('users', 'match_categories'),
            'total_users' => App\Models\User::count(),
            'users_with_categories_count' => $usersWithCategories->count()
        ];
        
        return response()->json($output, 200, [], JSON_PRETTY_PRINT);
    })->name('debug.match_categories');
    
    // ✅ USER DEBUG
    Route::get('/debug/user/{id}', function ($id) {
        if (!app()->environment('local')) {
            abort(403, 'Only available in local environment');
        }
        
        $user = \App\Models\User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        return response()->json([
            'user_id' => $user->id,
            'full_name' => $user->full_name,
            'raw_debug' => $user->getRawMatchCategories(),
            'match_categories' => $user->match_categories,
            'has_friends' => $user->hasMatchCategory('friends'),
            'has_jobs' => $user->hasMatchCategory('jobs'),
        ], 200, [], JSON_PRETTY_PRINT);
    })->name('debug.user');
    
    // ✅ UPDATE MATCH CATEGORIES TEST
    Route::post('/debug/update-match-categories', function (\Illuminate\Http\Request $request) {
        if (!app()->environment('local')) {
            abort(403, 'Only available in local environment');
        }
        
        $user = \Illuminate\Support\Facades\Auth::user();
        
        $categories = $request->input('categories', ['friends', 'jobs']); // default test data
        $user->match_categories = $categories;
        $user->save();
        
        return response()->json([
            'message' => 'Updated successfully',
            'before' => $user->getOriginal('match_categories'),
            'after' => $user->fresh()->match_categories,
            'raw_check' => $user->fresh()->getRawMatchCategories()
        ], 200, [], JSON_PRETTY_PRINT);
    })->name('debug.update_match_categories');
    
    // ✅ COMMUNITY DEBUG ROUTES
    Route::get('/debug/community/permissions/{groupId}', function($groupId) {
        if (!app()->environment('local')) {
            abort(403, 'Only available in local environment');
        }
        
        $user = Auth::user();
        $group = \App\Models\ChatGroup::find($groupId);
        
        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'role' => $user->role
            ],
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'creator_id' => $group->creator_id,
                'moderator_id' => $group->moderator_id,
                'is_approved' => $group->is_approved
            ],
            'permissions' => [
                'can_post' => (new \App\Http\Controllers\CommunityController())->checkPostPermission($user, $group),
                'is_admin' => $user->role === 'admin',
                'is_creator' => $group->creator_id === $user->id,
                'is_moderator' => $group->moderator_id === $user->id
            ]
        ], 200, [], JSON_PRETTY_PRINT);
    })->name('debug.community.permissions');
});

// ===================================================================
// TEST DATA SEEDING ROUTES (Development Only)
// ===================================================================

Route::get('/debug/seed-test-data', function () {
    if (!app()->environment('local')) {
        abort(403, 'Only available in local environment');
    }
    
    // Create test users with match categories
    $testUsers = [
        [
            'full_name' => 'Test User 1',
            'username' => 'testuser1',
            'email' => 'test1@student.unud.ac.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'mahasiswa',
            'is_verified' => true,
            'prodi' => 'Teknik Informatika',
            'fakultas' => 'Teknik',
            'gender' => 'Laki-laki',
            'description' => 'Test user 1 description',
            'interests' => ['programming', 'gaming'],
            'match_categories' => ['friends', 'jobs']
        ],
        [
            'full_name' => 'Test User 2',
            'username' => 'testuser2',
            'email' => 'test2@student.unud.ac.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'mahasiswa',
            'is_verified' => true,
            'prodi' => 'Sistem Informasi',
            'fakultas' => 'Teknik',
            'gender' => 'Perempuan',
            'description' => 'Test user 2 description',
            'interests' => ['design', 'music'],
            'match_categories' => ['friends', 'pkm', 'contest']
        ]
    ];
    
    foreach ($testUsers as $userData) {
        \App\Models\User::updateOrCreate(
            ['email' => $userData['email']],
            $userData
        );
    }
    
    return response()->json(['message' => 'Test data seeded successfully'], 200);
});

Route::get('/debug/create-test-user', function () {
    if (!app()->environment('local')) {
        abort(403, 'Only available in local environment');
    }
    
    try {
        // Cek apakah user test sudah ada
        $testUser = App\Models\User::where('email', 'test@student.unud.ac.id')->first();
        
        if (!$testUser) {
            // Buat user baru
            $testUser = App\Models\User::create([
                'full_name' => 'Test User Debug',
                'username' => 'testdebug',
                'email' => 'test@student.unud.ac.id',
                'password' => Hash::make('password'),
                'role' => 'mahasiswa',
                'is_verified' => true,
                'prodi' => 'Teknik Informatika',
                'fakultas' => 'Teknik',
                'gender' => 'Laki-laki',
                'description' => 'Test user untuk debugging',
                'interests' => ['programming', 'gaming'],
                'match_categories' => ['friends', 'jobs']
            ]);
            $action = 'created';
        } else {
            // Update user yang sudah ada
            $testUser->match_categories = ['friends', 'jobs', 'pkm'];
            $testUser->save();
            $action = 'updated';
        }
        
        return response()->json([
            'status' => 'success',
            'action' => $action,
            'user' => [
                'id' => $testUser->id,
                'name' => $testUser->full_name,
                'match_categories' => $testUser->match_categories,
                'raw_from_db' => DB::table('users')->where('id', $testUser->id)->value('match_categories')
            ]
        ], 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500, [], JSON_PRETTY_PRINT);
    }
});

// ===================================================================
// TEST COMMUNITY FEATURES (Development Only)
// ===================================================================

Route::get('/debug/community-setup', function() {
    if (!app()->environment('local')) {
        abort(403, 'Only available in local environment');
    }
    
    try {
        // Create test communities if they don't exist
        $communities = [
            [
                'name' => 'Pengumuman Umum',
                'description' => 'Saluran untuk pengumuman resmi dari kampus',
                'creator_id' => 1, // Assuming admin user ID is 1
                'is_approved' => true
            ],
            [
                'name' => 'Info Beasiswa',
                'description' => 'Informasi beasiswa dalam dan luar negeri',
                'creator_id' => 1,
                'is_approved' => true
            ],
            [
                'name' => 'PKM & Kompetisi',
                'description' => 'Info PKM, lomba, dan kompetisi mahasiswa',
                'creator_id' => 1,
                'is_approved' => true
            ]
        ];
        
        foreach ($communities as $communityData) {
            \App\Models\ChatGroup::updateOrCreate(
                ['name' => $communityData['name']],
                $communityData
            );
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Test communities created successfully',
            'communities' => \App\Models\ChatGroup::all(['id', 'name', 'creator_id', 'is_approved'])
        ], 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500, [], JSON_PRETTY_PRINT);
    }
});

// ===================================================================
// API TESTING ROUTES (Development Only)
// ===================================================================

Route::get('/debug/test-routes', function() {
    if (!app()->environment('local')) {
        abort(403, 'Only available in local environment');
    }
    
    $routes = [
        'Community Routes' => [
            'GET /community' => route('community'),
            'POST /community/send-message' => route('community.send_message'),
            'POST /community/reactions' => route('community.add_reaction'),
            'POST /community/comments' => route('community.add_comment'),
            'GET /community/permissions' => route('community.get_permissions'),
        ],
        'Debug Routes' => [
            'GET /debug/match-categories' => route('debug.match_categories'),
            'GET /debug/user/{id}' => url('/debug/user/1'),
            'POST /debug/update-match-categories' => route('debug.update_match_categories'),
        ]
    ];
    
    return response()->json([
        'status' => 'success',
        'available_routes' => $routes,
        'note' => 'These routes are available for testing the community features'
    ], 200, [], JSON_PRETTY_PRINT);
});