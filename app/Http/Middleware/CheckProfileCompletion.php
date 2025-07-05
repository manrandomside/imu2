<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckProfileCompletion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user sudah login
        if (Auth::check()) {
            $user = Auth::user();

            // 1. Cek Kelengkapan Profil Dasar
            $isBasicProfileComplete = true;
            $requiredProfileFields = ['prodi', 'fakultas', 'gender', 'description'];

            foreach ($requiredProfileFields as $field) {
                if (empty($user->$field)) {
                    $isBasicProfileComplete = false;
                    break;
                }
            }
            if (empty($user->interests) || !is_array($user->interests) || count($user->interests) === 0) {
                $isBasicProfileComplete = false;
            }

            // 2. Cek Kelengkapan Kategori Match
            $isMatchCategoriesComplete = true;
            if (empty($user->match_categories) || !is_array($user->match_categories) || count($user->match_categories) === 0) {
                $isMatchCategoriesComplete = false;
            }

            // --- Logika Redirect Paksa ---

            // Jika profil dasar belum lengkap DAN user tidak sedang di halaman profile.setup
            if (!$isBasicProfileComplete && !$request->routeIs('profile.setup')) {
                // Simpan URL yang ingin dituju user setelah melengkapi profil
                session()->put('url.intended', $request->url());
                return redirect()->route('profile.setup')->with('info', 'Mohon lengkapi profil Anda untuk dapat mengakses fitur lainnya.');
            }

            // Jika profil dasar sudah lengkap TETAPI kategori match belum lengkap
            // DAN user tidak sedang di halaman match.setup
            if ($isBasicProfileComplete && !$isMatchCategoriesComplete && !$request->routeIs('match.setup')) {
                // Simpan URL yang ingin dituju user setelah melengkapi kategori match
                session()->put('url.intended', $request->url());
                return redirect()->route('match.setup')->with('info', 'Mohon pilih kategori yang Anda cari untuk dapat mengakses fitur lainnya.');
            }
        }

        // Lanjutkan request jika user belum login atau semua profil sudah lengkap,
        // atau user sedang di halaman setup yang benar untuk melengkapi profil/kategori.
        return $next($request);
    }
}
