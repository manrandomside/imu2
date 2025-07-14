<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request untuk role-based access control
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles  Comma-separated list of allowed roles
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('Unauthenticated access attempt to role-protected route', [
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authentication required'
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::user();
        $allowedRoles = explode(',', $roles);
        
        // Clean up role names (remove whitespace)
        $allowedRoles = array_map('trim', $allowedRoles);

        // Check if user has any of the required roles
        $hasPermission = in_array($user->role, $allowedRoles);

        if (!$hasPermission) {
            Log::warning('Role-based access denied', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'required_roles' => $allowedRoles,
                'route' => $request->route()->getName(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient permissions',
                    'required_roles' => $allowedRoles,
                    'user_role' => $user->role
                ], 403);
            }

            return redirect()->route('home')->with('error', $this->getAccessDeniedMessage($user->role, $allowedRoles));
        }

        // ✅ Log successful access for audit trail
        Log::info('Role-based access granted', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'route' => $request->route()->getName()
        ]);

        return $next($request);
    }

    /**
     * Get user-friendly access denied message
     */
    private function getAccessDeniedMessage(string $userRole, array $requiredRoles): string
    {
        $roleDisplayNames = [
            'admin' => 'Administrator',
            'moderator' => 'Moderator',
            'mahasiswa' => 'Mahasiswa',
            'alumni' => 'Alumni',
            'tenaga_pendidik' => 'Tenaga Pendidik'
        ];

        $userRoleDisplay = $roleDisplayNames[$userRole] ?? ucfirst($userRole);
        $requiredRoleDisplay = array_map(function($role) use ($roleDisplayNames) {
            return $roleDisplayNames[$role] ?? ucfirst($role);
        }, $requiredRoles);

        if (count($requiredRoleDisplay) === 1) {
            return "Akses ditolak. Halaman ini hanya untuk {$requiredRoleDisplay[0]}. Role Anda: {$userRoleDisplay}.";
        } else {
            $lastRole = array_pop($requiredRoleDisplay);
            $roleList = implode(', ', $requiredRoleDisplay) . ' atau ' . $lastRole;
            return "Akses ditolak. Halaman ini hanya untuk {$roleList}. Role Anda: {$userRoleDisplay}.";
        }
    }
}

// ===============================================
// ✅ ADDITIONAL MIDDLEWARE: AdminOnly
// ===============================================

class AdminOnlyMiddleware
{
    /**
     * Handle admin-only access
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Authentication required'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->isAdmin()) {
            Log::warning('Admin-only access denied', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->route()->getName(),
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

// ===============================================
// ✅ ADDITIONAL MIDDLEWARE: ModeratorOrAdmin
// ===============================================

class ModeratorOrAdminMiddleware
{
    /**
     * Handle moderator or admin access
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Authentication required'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->hasModeratorPrivileges()) {
            Log::warning('Moderator/Admin access denied', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->route()->getName(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Moderator or Administrator access required'
                ], 403);
            }

            return redirect()->route('home')->with('error', 'Akses ditolak. Halaman ini hanya untuk Moderator atau Administrator.');
        }

        return $next($request);
    }
}

// ===============================================
// ✅ MIDDLEWARE REGISTRATION for app/Http/Kernel.php
// ===============================================

/*
Add these to your app/Http/Kernel.php in the $middlewareAliases array:

'role' => \App\Http\Middleware\RoleMiddleware::class,
'admin' => \App\Http\Middleware\AdminOnlyMiddleware::class,
'moderator' => \App\Http\Middleware\ModeratorOrAdminMiddleware::class,

Usage examples in routes:
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin only routes
});

Route::middleware(['auth', 'role:moderator,admin'])->group(function () {
    // Moderator or Admin routes
});

Route::middleware(['auth', 'admin'])->group(function () {
    // Admin only (shorthand)
});

Route::middleware(['auth', 'moderator'])->group(function () {
    // Moderator or Admin (shorthand)
});
*/