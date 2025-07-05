<?php

use Illuminate\Support\Facades\Route; // Pastikan baris ini ada dan benar
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityController;

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

    // Rute untuk halaman community chat
    Route::get('/community', [CommunityController::class, 'showCommunityChatPage'])->name('community');
    Route::post('/community/send-message', [CommunityController::class, 'sendGroupMessage'])->name('community.send_message');

    // Rute untuk halaman profil pengguna (view-only)
    Route::get('/profile', [AuthController::class, 'showUserProfilePage'])->name('user.profile');

    // Rute POST untuk menyimpan interaksi (like/dislike)
    Route::post('/user/interact', [ProfileController::class, 'storeInteraction'])->name('user.interact');

    // Tambahkan rute untuk Update Profil (akan kita buat nanti jika ada form update di halaman user.profile)
    // Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
});
