<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Authentication required'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has admin privileges
        if (!$user->isAdmin()) {
            Log::warning('Admin-only access denied', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->route() ? $request->route()->getName() : 'unknown',
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Administrator access required'
                ], 403);
            }

            return redirect()->route('home')->with('error', 'Akses ditolak. Halaman ini hanya untuk Administrator.');
        }

        return $next($request);
    }
}