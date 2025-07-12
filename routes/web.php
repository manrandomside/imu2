<?php

use Illuminate\Support\Facades\Route; // Pastikan baris ini ada dan benar
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\NotificationController; // ✅ TAMBAHAN: Import NotificationController

// Rute default, bisa diarahkan ke halaman login atau register nantinya
Route::get('/', function () {
    return view('welcome');
});

// --- Rute Autentikasi (Tidak Dilindungi Middleware) ---
// Rute Registrasi Mahasiswa
Route::get('/register/student', [AuthController::class, 'showRegisterStudentForm'])->name('register.student');
Route::post('/register/student', [AuthController::class, 'registerStudent'])->name('register.store.student');

// Rute Registrasi Alumni
Route::get('/register/alumni', [AuthController::class, 'showRegisterAlumniForm'])->name('register.alumni'); // DIKOREKSI: Route.get menjadi Route::get
Route::post('/register/alumni', [AuthController::class, 'registerAlumni'])->name('register.store.alumni');

// Rute Login
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

// Rute Logout
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Rute Halaman Notifikasi Verifikasi Alumni (Tidak perlu dilindungi, karena ini landing page setelah daftar)
Route::get('/alumni/verification-pending', [AuthController::class, 'showAlumniVerificationPendingPage'])->name('alumni.verification.pending');

// Rute Setup Profil dan Setup Kategori Match (Harus bisa diakses bahkan jika profil belum lengkap, tetapi hanya setelah login)
Route::middleware('auth')->group(function () {
    Route::get('/profile/setup', [AuthController::class, 'showProfileSetupForm'])->name('profile.setup');
    Route::post('/profile/store', [ProfileController::class, 'storeBasicProfile'])->name('profile.store');

    // DITAMBAHKAN: Rute POST untuk menyimpan kategori matching
    Route::post('/match/store-categories', [ProfileController::class, 'storeMatchCategories'])->name('profile.store_match_categories');
});


// --- Rute yang Dilindungi (Memerlukan Login) - Untuk sementara, cek kelengkapan profil DINOAKTIFKAN ---
// Middleware 'profile_complete' dihapus sementara
Route::middleware(['auth'])->group(function () {
    // Rute untuk halaman home feed (Setelah login dan profil lengkap)
    Route::get('/home', [AuthController::class, 'showHomePage'])->name('home');

    // Rute untuk halaman set up matching (Diakses sebagai bagian dari onboarding, atau edit)
    Route::get('/match/setup', [AuthController::class, 'showMatchSetupForm'])->name('match.setup');

    // Rute untuk halaman finding people
    Route::get('/find-people', [AuthController::class, 'showFindingPeoplePage'])->name('find.people');

    // Rute untuk halaman personal chat
    Route::get('/chat/personal', [ChatController::class, 'showPersonalChatPage'])->name('chat.personal');
    Route::post('/chat/send-message', [ChatController::class, 'sendMessage'])->name('chat.send_message');
    
    // DITAMBAHKAN: API endpoint untuk real-time chat (opsional)
    Route::get('/chat/messages', [ChatController::class, 'getMessages'])->name('chat.get_messages');

    // ✅ UPDATED: Rute untuk halaman community chat + API endpoints baru
    Route::get('/community', [CommunityController::class, 'showCommunityChatPage'])->name('community');
    Route::post('/community/send-message', [CommunityController::class, 'sendGroupMessage'])->name('community.send_message');
    
    // ✅ NEW: API endpoints untuk real-time community features
    Route::get('/community/messages', [CommunityController::class, 'getMessages'])->name('community.get_messages');
    Route::get('/community/stats', [CommunityController::class, 'getStats'])->name('community.stats');

    // Rute untuk halaman profil pengguna (view-only)
    Route::get('/profile', [AuthController::class, 'showUserProfilePage'])->name('user.profile');

    // Rute POST untuk menyimpan interaksi (like/dislike)
    Route::post('/user/interact', [ProfileController::class, 'storeInteraction'])->name('user.interact');

    // ✅ NOTIFICATION ROUTES - NEW!
    Route::get('/notifications/count', [NotificationController::class, 'getUnreadCount'])->name('notifications.count');
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.api');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all_read');
    Route::post('/notifications/like-back', [NotificationController::class, 'likeBack'])->name('notifications.like_back');
    Route::get('/notifications/page', [NotificationController::class, 'index'])->name('notifications.index');

    // Tambahkan rute untuk Update Profil (akan kita buat nanti jika ada form update di halaman user.profile)
    // Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
});

Route::middleware(['auth'])->group(function () {
    // Existing community routes
    Route::get('/community', [CommunityController::class, 'showCommunityChatPage'])->name('community');
    Route::post('/community/send-message', [CommunityController::class, 'sendGroupMessage'])->name('community.send_message');
    Route::get('/community/messages', [CommunityController::class, 'getMessages'])->name('community.get_messages');
    
    // ✅ NEW: Reactions System Routes
    Route::post('/community/reactions', [CommunityController::class, 'addReaction'])->name('community.add_reaction');
    
    // ✅ NEW: Comments System Routes  
    Route::post('/community/comments', [CommunityController::class, 'addComment'])->name('community.add_comment');
    Route::get('/community/comments', [CommunityController::class, 'loadComments'])->name('community.load_comments');
    
    // ✅ NEW: File Management Routes (untuk Phase 2 nanti)
    Route::delete('/community/attachments/{messageId}', [CommunityController::class, 'deleteAttachment'])->name('community.delete_attachment');
});

// Debug routes (hanya untuk development)
Route::middleware(['auth'])->group(function () {
    Route::get('/debug/match-categories', [DebugController::class, 'debugMatchCategories'])->name('debug.match_categories');
    Route::get('/debug/query-filtering', [DebugController::class, 'debugQueryFiltering'])->name('debug.query_filtering');
    
    // Test route untuk cek data user tertentu
    Route::get('/debug/user/{id}', function ($id) {
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
    
    // Test route untuk update match categories
    Route::post('/debug/update-match-categories', function (\Illuminate\Http\Request $request) {
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
});

// Test seeding route (hanya untuk development)
Route::get('/debug/seed-test-data', function () {
    if (!\Illuminate\Support\Facades\App::environment('local')) {
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

Route::get('/debug-match-categories', function () {
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
    
    // 4. Test JSON operations
    $testUser = $usersWithCategories->first();
    if ($testUser) {
        $output['json_test'] = [
            'original' => $testUser->match_categories,
            'json_encode' => json_encode($testUser->match_categories),
            'json_decode_test' => json_decode(json_encode($testUser->match_categories), true),
            'type_check' => gettype($testUser->match_categories)
        ];
    }
    
    return response()->json($output, 200, [], JSON_PRETTY_PRINT);
});

// Route untuk update test data
Route::get('/debug-create-test-user', function () {
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