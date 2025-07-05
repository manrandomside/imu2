<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\UserInteraction;
use Illuminate\Support\Facades\DB; // Ditambahkan: Untuk debugging SQL, meskipun tidak digunakan dalam final query

class AuthController extends Controller
{
    /**
     * Menampilkan halaman registrasi mahasiswa.
     */
    public function showRegisterStudentForm()
    {
        return view('auth.register_student');
    }

    /**
     * Menangani proses registrasi mahasiswa.
     */
    public function registerStudent(Request $request)
    {
        // 1. Validasi Data Input
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'regex:/^[^@]+@student\.unud\.ac\.id$/i'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username ini sudah digunakan. Silakan pilih yang lain.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email terlalu panjang.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.',
            'email.regex' => 'Email harus menggunakan domain @student.unud.ac.id.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // 2. Buat User Baru di Database
        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'mahasiswa',
            'is_verified' => true,
        ]);

        // 3. Redirect ke Halaman Login dengan pesan sukses
        return redirect()->route('login')->with('success', 'Akun mahasiswa berhasil didaftarkan! Silakan login.');
    }


    /**
     * Menampilkan halaman registrasi alumni.
     */
    public function showRegisterAlumniForm()
    {
        return view('auth.register_alumni');
    }

    /**
     * Menangani proses registrasi alumni.
     */
    public function registerAlumni(Request $request)
    {
        // 1. Validasi Data Input untuk Alumni
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => [
                'required_without:verification_doc',
                'nullable',
                'string',
                'email',
                'max:255',
                'unique:users'
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'verification_doc' => [
                'required_without:email',
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:3072' // Max 3MB (3072 KB)
            ],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username ini sudah digunakan. Silakan pilih yang lain.',
            'email.required_without' => 'Email atau dokumen verifikasi wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'verification_doc.required_without' => 'Dokumen verifikasi atau email wajib diisi.',
            'verification_doc.file' => 'Dokumen verifikasi harus berupa file.',
            'verification_doc.mimes' => 'Format dokumen harus JPG, JPEG, PNG, atau PDF.',
            'verification_doc.max' => 'Ukuran dokumen maksimal 3 MB.',
        ]);

        // 2. Upload Dokumen Verifikasi (jika ada)
        $filePath = null;
        if ($request->hasFile('verification_doc')) {
            $filePath = $request->file('verification_doc')->store('public/alumni_verification');
            $filePath = str_replace('public/', 'storage/', $filePath);
        }

        // 3. Buat User Baru di Database
        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email, // Email bisa null jika hanya doc yang diisi
            'password' => Hash::make($request->password),
            'role' => 'alumni',
            'is_verified' => false, // Alumni belum terverifikasi, menunggu admin
            'verification_doc_path' => $filePath, // Simpan path dokumen yang diunggah (bisa null)
        ]);

        // 4. Redirect ke Halaman Notifikasi Verifikasi
        return redirect()->route('alumni.verification.pending')->with('success', 'Registrasi berhasil! Akun Anda sedang dalam proses verifikasi dan akan aktif dalam 1x24 jam.');
    }


    /**
     * Menampilkan halaman login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Menangani proses login pengguna.
     */
    public function login(Request $request)
    {
        // 1. Validasi Kredensial Input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // 2. Coba Autentikasi Pengguna
        if (Auth::attempt($credentials)) {
            // Autentikasi berhasil
            $user = Auth::user(); // Dapatkan instance user yang sedang login

            // 3. Cek Status Verifikasi untuk Alumni
            if ($user->role === 'alumni' && !$user->is_verified) {
                // Jika alumni dan belum diverifikasi, logout dan redirect ke halaman pending
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('alumni.verification.pending')->with('error', 'Akun Anda belum diverifikasi. Mohon tunggu proses verifikasi.');
            }

            // 4. Regenerate sesi untuk mencegah Session Fixation
            $request->session()->regenerate();

            // 5. Cek kelengkapan profil dasar
            $isBasicProfileComplete = true;
            $requiredProfileFields = ['prodi', 'fakultas', 'gender', 'description'];

            foreach ($requiredProfileFields as $field) {
                if (empty($user->$field)) {
                    $isBasicProfileComplete = false;
                    break;
                }
            }
            // Periksa interests secara terpisah (jika kosong atau bukan array dengan item)
            if (empty($user->interests) || !is_array($user->interests) || count($user->interests) === 0) {
                $isBasicProfileComplete = false;
            }

            // 6. Cek kelengkapan kategori match
            $isMatchCategoriesComplete = true;
            if (empty($user->match_categories) || !is_array($user->match_categories) || count($user->match_categories) === 0) {
                $isMatchCategoriesComplete = false;
            }

            // Arahkan ke profile.setup jika belum lengkap, atau ke home jika sudah
            if (!$isBasicProfileComplete) {
                return redirect()->route('profile.setup')->with('success', 'Selamat datang! Silakan lengkapi profil Anda.');
            } elseif (!$isMatchCategoriesComplete) {
                return redirect()->route('match.setup')->with('info', 'Profil dasar Anda sudah lengkap! Sekarang pilih kategori yang Anda cari.');
            } else {
                // Jika kedua tahap onboarding sudah lengkap, arahkan ke intended URL atau home
                return redirect()->intended(route('home'))->with('success', 'Selamat datang kembali!');
            }
        }

        // 6. Autentikasi Gagal
        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak cocok dengan catatan kami.',
        ])->onlyInput('email'); // Hanya tampilkan error di field email
    }

    /**
     * Menangani proses logout pengguna.
     */
    public function logout(Request $request)
    {
        Auth::logout(); // Logout pengguna

        $request->session()->invalidate(); // Hapus sesi
        $request->session()->regenerateToken(); // Buat ulang token CSRF

        return redirect('/login')->with('success', 'Anda telah berhasil logout.'); // Redirect ke halaman login
    }


    /**
     * Menampilkan halaman setup profil.
     */
    public function showProfileSetupForm()
    {
        return view('profile_setup');
    }

    /**
     * Menampilkan halaman setup matching.
     */
    public function showMatchSetupForm()
    {
        return view('match_setup');
    }

    /**
     * Menampilkan halaman finding people.
     */
    public function showFindingPeoplePage()
    {
        $currentUserId = Auth::id();
        $currentUser = Auth::user();

        // --- DEBUGGING START ---
        // dd([
        //     'currentUser' => $currentUser ? $currentUser->toArray() : null,
        //     'currentUserInterests' => $currentUser->interests,
        //     'currentUserMatchCategories' => $currentUser->match_categories,
        //     'interactedUserIds' => UserInteraction::where('user_id', $currentUserId)->pluck('target_user_id')->toArray(),
        //     'currentAuthId' => $currentUserId,
        // ]);
        // --- DEBUGGING END ---

        $interactedUserIds = UserInteraction::where('user_id', $currentUserId)
                                            ->pluck('target_user_id')
                                            ->toArray();

        $currentUserInterests = $currentUser->interests; 
        $currentUserMatchCategories = $currentUser->match_categories; 

        $usersToDisplayQuery = User::where('id', '!=', $currentUserId)
                                ->where('is_verified', true)
                                ->whereNotNull('prodi')
                                ->whereNotNull('fakultas')
                                ->whereNotNull('gender')
                                ->whereNotNull('description')
                                ->whereNotNull('interests')
                                ->whereNotNull('match_categories')
                                ->whereNotIn('id', $interactedUserIds);

        // --- FILTER KUNCI BERDASARKAN KATEGORI MATCH (PENTING! INI FILTER UTAMA) ---
        if (is_array($currentUserMatchCategories) && count($currentUserMatchCategories) > 0) {
            $usersToDisplayQuery->where(function ($query) use ($currentUserMatchCategories) {
                foreach ($currentUserMatchCategories as $category) {
                    // Solusi yang lebih handal untuk JSON di SQLite/MySQL
                    // Mencari apakah string JSON mengandung kategori yang dicari
                    // Menggunakan JSON_EXTRACT untuk mengambil seluruh array JSON sebagai string
                    // dan kemudian LIKE untuk mencari kecocokan substring.
                    // Ini mengatasi masalah format JSON yang tidak konsisten atau case sensitivity.
                    $query->orWhereRaw('LOWER(JSON_EXTRACT(match_categories, "$")) LIKE ?', ['%"' . strtolower($category) . '"%']);
                }
            });
        } else {
            // Jika user yang login tidak punya kategori match (harusnya dicegah onboarding),
            // maka tidak ada user lain yang akan cocok dengan kriteria kategori ini.
            $usersToDisplayQuery->whereRaw('1 = 0'); // Jika kategori kosong, jangan tampilkan siapa pun
        }

        $usersToDisplay = $usersToDisplayQuery->inRandomOrder()->limit(5)->get();

        return view('finding_people', compact('usersToDisplay'));
    }

    /**
     * Menampilkan halaman home feed.
     */
    public function showHomePage()
    {
        return view('home_feed');
    }

    /**
     * Menampilkan halaman profil pengguna.
     */
    public function showUserProfilePage()
    {
        $user = Auth::user();

        return view('user_profile', compact('user'));
    }

    /**
     * Menampilkan halaman notifikasi verifikasi alumni pending.
     */
    public function showAlumniVerificationPendingPage()
    {
        return view('auth.alumni_verification_pending');
    }
}
