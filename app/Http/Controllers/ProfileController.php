<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\UserInteraction;
use App\Models\UserMatch;
use App\Models\Notification;

class ProfileController extends Controller
{
    /**
     * Menangani penyimpanan/update profil dasar pengguna (dari profile_setup).
     * Metode ini sebelumnya bernama 'store', namun diubah menjadi 'storeBasicProfile'.
     */
    public function storeBasicProfile(Request $request) // Nama metode diubah dari 'store'
    {
        $user = Auth::user(); // Dapatkan user yang sedang login

        // 1. Enhanced validation termasuk social media links
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'prodi' => ['required', 'string', 'max:255'],
            'fakultas' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])], // Pastikan sesuai enum di DB
            'description' => ['nullable', 'string', 'max:1000'], // Deskripsi bisa kosong
            'interests' => ['required', 'json'], // Interests wajib ada dan format JSON
            
            // ✅ TAMBAHAN: Validation untuk social links
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
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
            
            // ✅ TAMBAHAN: Error messages untuk social links
            'linkedin_url.url' => 'Format URL LinkedIn tidak valid.',
            'github_url.url' => 'Format URL GitHub tidak valid.',
            'instagram_url.url' => 'Format URL Instagram tidak valid.',
        ]);

        // Decode interests dari JSON string menjadi array PHP
        $interestsArray = json_decode($request->interests, true);

        // Validasi tambahan: Pastikan array interests tidak kosong setelah decode (maksimal 3 sudah di JS)
        if (!is_array($interestsArray) || count($interestsArray) === 0 || count($interestsArray) > 3) {
            return back()->withErrors(['interests' => 'Pilih antara 1 hingga 3 minat Anda.'])->withInput();
        }

        // ✅ TAMBAHAN: Process social media links
        $socialLinks = [];
        if ($request->filled('linkedin_url')) {
            try {
                $socialLinks['linkedin'] = $this->formatSocialUrl($request->linkedin_url, 'linkedin');
            } catch (\Exception $e) {
                return back()->withErrors(['linkedin_url' => $e->getMessage()])->withInput();
            }
        }
        if ($request->filled('github_url')) {
            try {
                $socialLinks['github'] = $this->formatSocialUrl($request->github_url, 'github');
            } catch (\Exception $e) {
                return back()->withErrors(['github_url' => $e->getMessage()])->withInput();
            }
        }
        if ($request->filled('instagram_url')) {
            try {
                $socialLinks['instagram'] = $this->formatSocialUrl($request->instagram_url, 'instagram');
            } catch (\Exception $e) {
                return back()->withErrors(['instagram_url' => $e->getMessage()])->withInput();
            }
        }

        // 2. Update Profil Pengguna
        $user->full_name = $request->full_name;
        $user->prodi = $request->prodi;
        $user->fakultas = $request->fakultas;
        $user->gender = $request->gender;
        $user->description = $request->description;
        $user->interests = $interestsArray;
        $user->social_links = $socialLinks; // ✅ TAMBAHAN: Update social links
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

        // Debugging: log input yang diterima
        \Log::info('storeMatchCategories called', [
            'user_id' => $user->id,
            'input_match_categories' => $request->input('match_categories'),
            'all_input' => $request->all()
        ]);

        // Validasi kategori match
        $request->validate([
            'match_categories' => ['required', 'string', 'min:1'], // Ubah dari 'json' ke 'string' untuk debugging yang lebih baik
        ], [
            'match_categories.required' => 'Pilih setidaknya satu kategori yang Anda cari.',
            'match_categories.string' => 'Format kategori tidak valid.',
            'match_categories.min' => 'Pilih setidaknya satu kategori yang Anda cari.',
        ]);

        $matchCategoriesInput = $request->input('match_categories');
        
        // Validasi dan decode JSON
        $matchCategoriesArray = json_decode($matchCategoriesInput, true);
        
        // Cek apakah JSON decode berhasil
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('JSON decode error in storeMatchCategories', [
                'input' => $matchCategoriesInput,
                'json_error' => json_last_error_msg()
            ]);
            
            return back()->withErrors([
                'match_categories' => 'Format kategori tidak valid. Silakan coba lagi.'
            ])->withInput();
        }

        // Validasi tambahan: Pastikan array match_categories tidak kosong setelah decode
        if (!is_array($matchCategoriesArray) || count($matchCategoriesArray) === 0) {
            \Log::warning('Empty or invalid match categories array', [
                'decoded_array' => $matchCategoriesArray,
                'is_array' => is_array($matchCategoriesArray)
            ]);
            
            return back()->withErrors([
                'match_categories' => 'Pilih setidaknya satu kategori yang Anda cari.'
            ])->withInput();
        }

        // Validasi kategori yang diizinkan (optional security check)
        $allowedCategories = ['friends', 'jobs', 'committee', 'pkm', 'kkn', 'contest'];
        $invalidCategories = array_diff($matchCategoriesArray, $allowedCategories);
        
        if (!empty($invalidCategories)) {
            \Log::warning('Invalid categories submitted', [
                'invalid_categories' => $invalidCategories,
                'submitted_categories' => $matchCategoriesArray
            ]);
            
            return back()->withErrors([
                'match_categories' => 'Kategori yang dipilih tidak valid: ' . implode(', ', $invalidCategories)
            ])->withInput();
        }

        // Debug: log sebelum save
        \Log::info('About to save match categories', [
            'user_id' => $user->id,
            'old_match_categories' => $user->match_categories,
            'new_match_categories' => $matchCategoriesArray
        ]);

        try {
            // Simpan match_categories
            $user->match_categories = $matchCategoriesArray;
            $saved = $user->save();
            
            // Debug: log setelah save
            \Log::info('Match categories saved', [
                'user_id' => $user->id,
                'save_result' => $saved,
                'saved_match_categories' => $user->fresh()->match_categories,
                'raw_check' => $user->fresh()->getRawMatchCategories()
            ]);
            
            if (!$saved) {
                throw new \Exception('Failed to save user data');
            }
            
        } catch (\Exception $e) {
            \Log::error('Error saving match categories', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors([
                'match_categories' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.'
            ])->withInput();
        }

        // Setelah kategori match disimpan, arahkan ke halaman home atau intended URL
        $intendedUrl = session()->pull('url.intended', route('home'));
        
        return redirect($intendedUrl)->with('success', 'Kategori pencarian Anda berhasil disimpan! Anda siap menjelajah IMU.');
    }

    /**
     * ✅ UPDATED: Menangani penyimpanan aksi like/dislike dari halaman Finding People.
     * DENGAN NOTIFICATION SYSTEM
     */
    public function storeInteraction(Request $request)
    {
        $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'action_type' => ['required', Rule::in(['like', 'dislike'])],
        ]);

        $currentUser = Auth::user();
        $targetUserId = $request->target_user_id;
        $actionType = $request->action_type;
        $matched = false;
        $likeNotificationCreated = false;

        // Pastikan user tidak meng-swipe dirinya sendiri
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

        // --- LOGIKA MATCHING & NOTIFICATION ---
        if ($actionType === 'like') {
            // Cek apakah target user juga sudah me-like user saat ini
            $mutualLike = UserInteraction::where('user_id', $targetUserId)
                                        ->where('target_user_id', $currentUser->id)
                                        ->where('action_type', 'like')
                                        ->first();

            if ($mutualLike) {
                // MUTUAL LIKE = MATCH!
                $user1 = min($currentUser->id, $targetUserId);
                $user2 = max($currentUser->id, $targetUserId);

                $match = UserMatch::firstOrCreate([
                    'user1_id' => $user1,
                    'user2_id' => $user2,
                ]);

                $matched = true;

                // ✅ Create match notifications for both users
                Notification::createMatchNotifications($currentUser->id, $targetUserId, $match->id);

            } else {
                // SINGLE LIKE = CREATE NOTIFICATION FOR TARGET USER
                Notification::createLikeNotification($targetUserId, $currentUser->id);
                $likeNotificationCreated = true;
            }
        }

        // --- RESPONSE BERDASARKAN AKSI ---
        if ($matched) {
            return response()->json([
                'status' => 'success',
                'message' => 'SELAMAT! TERJADI MATCH! 🎉',
                'matched' => true,
                'target_user_id' => $targetUserId,
                'action_type' => $actionType
            ], 200);
        } 
        elseif ($actionType === 'like' && $likeNotificationCreated) {
            return response()->json([
                'status' => 'success', 
                'message' => 'Like terkirim! Tunggu mereka like balik untuk match 💕',
                'matched' => false,
                'target_user_id' => $targetUserId,
                'action_type' => $actionType,
                'notification_sent' => true
            ], 200);
        }
        elseif ($actionType === 'dislike') {
            return response()->json([
                'status' => 'success',
                'message' => 'Pass. Mencari yang berikutnya...',
                'matched' => false,
                'target_user_id' => $targetUserId,
                'action_type' => $actionType
            ], 200);
        }
        else {
            // Fallback response
            return response()->json([
                'status' => 'success',
                'message' => 'Interaksi disimpan.',
                'matched' => false,
                'target_user_id' => $targetUserId,
                'action_type' => $actionType
            ], 200);
        }
    }

    // ===============================================
    // ✅ NEW: SOCIAL MEDIA LINKS METHODS
    // ===============================================

    /**
     * ✅ NEW: Show edit profile form dengan data social links
     */
    public function showEditProfile()
    {
        $user = Auth::user();
        return view('edit_profile', compact('user'));
    }

    /**
     * ✅ NEW: Update profile dari halaman edit (bukan setup)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'interests' => ['required', 'array', 'min:1', 'max:3'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'description.max' => 'Deskripsi terlalu panjang (maksimal 1000 karakter).',
            'interests.required' => 'Pilih minimal satu minat.',
            'interests.min' => 'Pilih minimal satu minat.',
            'interests.max' => 'Maksimal 3 minat yang dapat dipilih.',
            'linkedin_url.url' => 'Format URL LinkedIn tidak valid.',
            'github_url.url' => 'Format URL GitHub tidak valid.',
            'instagram_url.url' => 'Format URL Instagram tidak valid.',
        ]);

        // Process social links
        $socialLinks = [];
        if ($request->filled('linkedin_url')) {
            try {
                $socialLinks['linkedin'] = $this->formatSocialUrl($request->linkedin_url, 'linkedin');
            } catch (\Exception $e) {
                return back()->withErrors(['linkedin_url' => $e->getMessage()])->withInput();
            }
        }
        if ($request->filled('github_url')) {
            try {
                $socialLinks['github'] = $this->formatSocialUrl($request->github_url, 'github');
            } catch (\Exception $e) {
                return back()->withErrors(['github_url' => $e->getMessage()])->withInput();
            }
        }
        if ($request->filled('instagram_url')) {
            try {
                $socialLinks['instagram'] = $this->formatSocialUrl($request->instagram_url, 'instagram');
            } catch (\Exception $e) {
                return back()->withErrors(['instagram_url' => $e->getMessage()])->withInput();
            }
        }

        // Update user
        $user->update([
            'full_name' => $request->full_name,
            'description' => $request->description,
            'interests' => $request->interests,
            'social_links' => $socialLinks,
        ]);

        return redirect()->route('user.profile')->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * ✅ NEW: Method untuk format dan validasi social media URLs
     */
    private function formatSocialUrl($url, $platform)
    {
        // Tambahkan https:// jika belum ada
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        // Validasi URL berdasarkan platform
        switch ($platform) {
            case 'linkedin':
                if (!str_contains($url, 'linkedin.com')) {
                    throw new \Exception('URL LinkedIn tidak valid. Gunakan format: https://linkedin.com/in/username');
                }
                break;
            case 'github':
                if (!str_contains($url, 'github.com')) {
                    throw new \Exception('URL GitHub tidak valid. Gunakan format: https://github.com/username');
                }
                break;
            case 'instagram':
                if (!str_contains($url, 'instagram.com')) {
                    throw new \Exception('URL Instagram tidak valid. Gunakan format: https://instagram.com/username');
                }
                break;
        }

        return $url;
    }

    /**
     * ✅ Get profile completion percentage
     */
    public function getProfileCompletion()
    {
        $user = Auth::user();
        $completion = $user->getProfileCompletionPercentage();
        
        return response()->json([
            'completion' => $completion,
            'missing' => $this->getMissingProfileFields($user)
        ]);
    }

    /**
     * ✅ Get user's social links
     */
    public function getSocialLinks()
    {
        $user = Auth::user();
        return response()->json([
            'social_links' => $user->getFormattedSocialLinks(),
            'has_links' => $user->hasSocialLinks()
        ]);
    }

    /**
     * ✅ Remove specific social media link
     */
    public function removeSocialLink($platform)
    {
        $user = Auth::user();
        $socialLinks = $user->social_links ?? [];
        
        if (isset($socialLinks[$platform])) {
            unset($socialLinks[$platform]);
            $user->social_links = $socialLinks;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($platform) . ' link removed successfully'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Social media link not found'
        ], 404);
    }

    /**
     * ✅ Validate social media URL via AJAX
     */
    public function validateSocialUrl(Request $request)
    {
        $url = $request->input('url');
        $platform = $request->input('platform');
        
        if (!$url) {
            return response()->json(['valid' => true]); // Empty URL is valid
        }
        
        // Add https if missing
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid URL format'
            ]);
        }
        
        // Platform-specific validation
        $valid = false;
        $message = '';
        
        switch ($platform) {
            case 'linkedin':
                $valid = str_contains($url, 'linkedin.com');
                $message = $valid ? '' : 'Please enter a valid LinkedIn URL (linkedin.com)';
                break;
            case 'github':
                $valid = str_contains($url, 'github.com');
                $message = $valid ? '' : 'Please enter a valid GitHub URL (github.com)';
                break;
            case 'instagram':
                $valid = str_contains($url, 'instagram.com');
                $message = $valid ? '' : 'Please enter a valid Instagram URL (instagram.com)';
                break;
            default:
                $valid = true;
        }
        
        return response()->json([
            'valid' => $valid,
            'message' => $message,
            'formatted_url' => $url
        ]);
    }

    /**
     * ✅ Upload profile picture
     */
    public function uploadProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'profile_picture.required' => 'Please select a profile picture.',
            'profile_picture.image' => 'File must be an image.',
            'profile_picture.mimes' => 'Image must be JPEG, PNG, JPG, or GIF.',
            'profile_picture.max' => 'Image size must not exceed 2MB.'
        ]);
        
        $user = Auth::user();
        
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            
            // Store new profile picture
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            
            $user->profile_picture = $path;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'url' => Storage::url($path)
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'No file uploaded'
        ], 400);
    }

    /**
     * ✅ Enhanced method untuk batch update social links
     */
    public function updateSocialLinks(Request $request)
    {
        $request->validate([
            'linkedin_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
        ], [
            'linkedin_url.url' => 'Format URL LinkedIn tidak valid.',
            'github_url.url' => 'Format URL GitHub tidak valid.',
            'instagram_url.url' => 'Format URL Instagram tidak valid.',
        ]);
        
        $user = Auth::user();
        $socialLinks = [];
        
        // Process each platform
        $platforms = ['linkedin', 'github', 'instagram'];
        foreach ($platforms as $platform) {
            $fieldName = $platform . '_url';
            if ($request->filled($fieldName)) {
                try {
                    $socialLinks[$platform] = $this->formatSocialUrl($request->input($fieldName), $platform);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage()
                    ], 422);
                }
            }
        }
        
        $user->social_links = $socialLinks;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Social media links updated successfully',
            'social_links' => $user->getFormattedSocialLinks()
        ]);
    }

    /**
     * ✅ Helper method to get missing profile fields
     */
    private function getMissingProfileFields($user)
    {
        $missing = [];
        
        if (!$user->full_name) $missing[] = 'Full Name';
        if (!$user->description) $missing[] = 'About Me';
        if (!$user->interests || count($user->interests) === 0) $missing[] = 'Interests';
        if (!$user->prodi || !$user->fakultas) $missing[] = 'Academic Information';
        if (!$user->hasSocialLinks()) $missing[] = 'Social Media Links';
        
        return $missing;
    }

    /**
     * ✅ Get profile statistics for dashboard
     */
    public function getProfileStats()
    {
        $user = Auth::user();
        
        return response()->json([
            'completion_percentage' => $user->getProfileCompletionPercentage(),
            'has_complete_profile' => $user->hasCompleteProfile(),
            'missing_fields' => $user->getMissingProfileFields(),
            'social_links_count' => $user->getSocialLinksCount(),
            'has_social_links' => $user->hasSocialLinks(),
            'member_since' => $user->created_at->format('F Y'),
            'is_verified' => $user->is_verified
        ]);
    }

    /**
     * ✅ Bulk update profile fields
     */
    public function bulkUpdateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'interests' => 'sometimes|required|array|min:1|max:3',
            'social_links' => 'sometimes|array',
            'social_links.linkedin' => 'nullable|url|max:255',
            'social_links.github' => 'nullable|url|max:255', 
            'social_links.instagram' => 'nullable|url|max:255',
        ]);
        
        $updateData = [];
        
        // Update basic fields
        if ($request->has('full_name')) {
            $updateData['full_name'] = $request->full_name;
        }
        if ($request->has('description')) {
            $updateData['description'] = $request->description;
        }
        if ($request->has('interests')) {
            $updateData['interests'] = $request->interests;
        }
        
        // Update social links
        if ($request->has('social_links')) {
            $socialLinks = [];
            foreach ($request->social_links as $platform => $url) {
                if (!empty($url)) {
                    try {
                        $socialLinks[$platform] = $this->formatSocialUrl($url, $platform);
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => $e->getMessage()
                        ], 422);
                    }
                }
            }
            $updateData['social_links'] = $socialLinks;
        }
        
        $user->update($updateData);
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'completion_percentage' => $user->getProfileCompletionPercentage(),
            'social_links' => $user->getFormattedSocialLinks()
        ]);
    }
}