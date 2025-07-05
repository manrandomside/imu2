<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\UserInteraction;
use App\Models\UserMatch;

class ProfileController extends Controller
{
    /**
     * Menangani penyimpanan/update profil dasar pengguna (dari profile_setup).
     * Metode ini sebelumnya bernama 'store', namun diubah menjadi 'storeBasicProfile'.
     */
    public function storeBasicProfile(Request $request) // Nama metode diubah dari 'store'
    {
        $user = Auth::user(); // Dapatkan user yang sedang login

        // 1. Validasi Data Input Profil Dasar
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'prodi' => ['required', 'string', 'max:255'],
            'fakultas' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])], // Pastikan sesuai enum di DB
            'description' => ['nullable', 'string', 'max:1000'], // Deskripsi bisa kosong
            'interests' => ['required', 'json'], // Interests wajib ada dan format JSON
            // 'match_categories' TIDAK divalidasi di sini karena ini form Profile Setup
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'prodi.required' => 'Program studi wajib diisi.',
            'fakultas.required' => 'Fakultas wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Pilihan jenis kelamin tidak valid.',
            'description.max' => 'Deskripsi terlalu panjang (maksimal 1000 karakter).',
            'interests.required' => 'Pilih minimal satu minat Anda.',
            'interests.json' => 'Format minat tidak valid.',
        ]);

        // Decode interests dari JSON string menjadi array PHP
        $interestsArray = json_decode($request->interests, true);

        // Validasi tambahan: Pastikan array interests tidak kosong setelah decode (maksimal 3 sudah di JS)
        if (!is_array($interestsArray) || count($interestsArray) === 0 || count($interestsArray) > 3) {
            return back()->withErrors(['interests' => 'Pilih antara 1 hingga 3 minat Anda.'])->withInput();
        }

        // 2. Update Profil Pengguna
        $user->full_name = $request->full_name;
        $user->prodi = $request->prodi;
        $user->fakultas = $request->fakultas;
        $user->gender = $request->gender;
        $user->description = $request->description;
        $user->interests = $interestsArray;
        // Kolom 'match_categories' TIDAK diupdate di sini, akan diupdate di storeMatchCategories
        $user->save(); // Simpan perubahan ke database

        // 3. Redirect Pengguna Setelah Profil Dasar Lengkap, arahkan ke halaman setup matching
        return redirect()->route('match.setup')->with('success', 'Profil Anda berhasil disimpan! Sekarang pilih kategori yang Anda cari.');
    }

    /**
     * Menangani penyimpanan kategori matching pengguna (dari match_setup).
     */
    public function storeMatchCategories(Request $request)
    {
        $user = Auth::user();

        // Validasi kategori match
        $request->validate([
            'match_categories' => ['required', 'json', 'min:1'], // Harus ada, format JSON, minimal 1 pilihan
        ], [
            'match_categories.required' => 'Pilih setidaknya satu kategori yang Anda cari.',
            'match_categories.json' => 'Format kategori tidak valid.',
            'match_categories.min' => 'Pilih setidaknya satu kategori yang Anda cari.',
        ]);

        $matchCategoriesArray = json_decode($request->match_categories, true);

        // Validasi tambahan: Pastikan array match_categories tidak kosong setelah decode
        if (!is_array($matchCategoriesArray) || count($matchCategoriesArray) === 0) {
            return back()->withErrors(['match_categories' => 'Pilih setidaknya satu kategori yang Anda cari.'])->withInput();
        }

        $user->match_categories = $matchCategoriesArray; // Menyimpan match_categories
        $user->save();

        // Setelah kategori match disimpan, arahkan ke halaman home atau intended URL
        $intendedUrl = session()->pull('url.intended', route('home'));
        return redirect($intendedUrl)->with('success', 'Kategori pencarian Anda berhasil disimpan! Anda siap menjelajah IMU.');
    }

    /**
     * Menangani penyimpanan aksi like/dislike dari halaman Finding People.
     */
    public function storeInteraction(Request $request)
    {
        $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'], // ID user target harus ada di tabel users
            'action_type' => ['required', Rule::in(['like', 'dislike'])], // Aksi harus 'like' atau 'dislike'
        ]);

        $currentUser = Auth::user();
        $targetUserId = $request->target_user_id;
        $actionType = $request->action_type;
        $matched = false; // Flag untuk menandakan apakah terjadi match

        // Pastikan user tidak meng-swipe dirinya sendiri (meskipun sudah difilter di query)
        if ($currentUser->id == $targetUserId) {
            return response()->json(['status' => 'error', 'message' => 'Tidak bisa menyukai diri sendiri.'], 400);
        }

        // Simpan atau perbarui interaksi
        $interaction = UserInteraction::updateOrCreate(
            [
                'user_id' => $currentUser->id,
                'target_user_id' => $targetUserId
            ],
            [
                'action_type' => $actionType
            ]
        );

        // --- LOGIKA MATCHING DIMULAI DI SINI ---
        if ($actionType === 'like') {
            // Cek apakah target user juga sudah me-like user saat ini
            $mutualLike = UserInteraction::where('user_id', $targetUserId)
                                        ->where('target_user_id', $currentUser->id)
                                        ->where('action_type', 'like')
                                        ->first();

            if ($mutualLike) {
                // Jika ada mutual like, catat sebagai match
                // Pastikan user1_id selalu lebih kecil dari user2_id untuk uniqueness
                $user1 = min($currentUser->id, $targetUserId);
                $user2 = max($currentUser->id, $targetUserId);

                UserMatch::firstOrCreate([ // Menggunakan Model UserMatch yang sudah dibuat
                    'user1_id' => $user1,
                    'user2_id' => $user2,
                ]);

                $matched = true;
            }
        }
        // --- LOGIKA MATCHING BERAKHIR DI SINI ---

        $responseMessage = 'Interaksi disimpan.';
        if ($matched) {
            $responseMessage = 'SELAMAT! TERJADI MATCH!';
        } else if ($interaction->wasRecentlyCreated) {
            $responseMessage = 'Interaksi baru disimpan.';
        } else {
            $responseMessage = 'Interaksi diperbarui.';
        }


        return response()->json([
            'status' => 'success',
            'message' => $responseMessage,
            'matched' => $matched, // Kirim status match ke frontend
            'target_user_id' => $targetUserId // Kembalikan ID user target
        ], 200);
    }
}
