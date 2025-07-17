<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// ✅ EXPLICIT CONTROLLER IMPORTS - FIXED
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ModeratorController;
use App\Http\Controllers\ContentSubmissionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\AlumniApprovalController;
use App\Http\Controllers\Admin\DashboardController;

// ===================================================================
// DEFAULT ROUTE
// ===================================================================

Route::get('/', function () {
    return view('welcome');
});

// ===================================================================
// AUTHENTICATION ROUTES (No Middleware)
// ===================================================================

Route::get('/register/student', [AuthController::class, 'showRegisterStudentForm'])->name('register.student');
Route::post('/register/student', [AuthController::class, 'registerStudent'])->name('register.store.student');

Route::get('/register/alumni', [AuthController::class, 'showRegisterAlumniForm'])->name('register.alumni');
Route::post('/register/alumni', [AuthController::class, 'registerAlumni'])->name('register.store.alumni');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

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
    
    // Core Application Pages
    Route::get('/home', [AuthController::class, 'showHomePage'])->name('home');
    Route::get('/match/setup', [AuthController::class, 'showMatchSetupForm'])->name('match.setup');
    Route::get('/find-people', [AuthController::class, 'showFindingPeoplePage'])->name('find.people');
    Route::get('/profile', [AuthController::class, 'showUserProfilePage'])->name('user.profile');
    
    // User Interactions
    Route::post('/user/interact', [ProfileController::class, 'storeInteraction'])->name('user.interact');
    
    // Personal Chat Routes
    Route::get('/chat/personal', [ChatController::class, 'showPersonalChatPage'])->name('chat.personal');
    Route::post('/chat/send-message', [ChatController::class, 'sendMessage'])->name('chat.send_message');
    Route::get('/chat/messages', [ChatController::class, 'getMessages'])->name('chat.get_messages');
    
    // Notification Routes
    Route::get('/notifications/count', [NotificationController::class, 'getUnreadCount'])->name('notifications.count');
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.api');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all_read');
    Route::post('/notifications/like-back', [NotificationController::class, 'likeBack'])->name('notifications.like_back');
    Route::get('/notifications/page', [NotificationController::class, 'index'])->name('notifications.index');
});

// ===================================================================
// ENHANCED COMMUNITY ROUTES (Complete Functionality)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // Basic Community Routes
    Route::get('/community', [CommunityController::class, 'showCommunityChatPage'])->name('community');
    Route::post('/community/send-message', [CommunityController::class, 'sendGroupMessage'])->name('community.send_message');
    Route::get('/community/messages', [CommunityController::class, 'getMessages'])->name('community.get_messages');
    Route::get('/community/stats', [CommunityController::class, 'getStats'])->name('community.stats');
    
    // Reactions & Comments System
    Route::post('/community/reactions', [CommunityController::class, 'addReaction'])->name('community.add_reaction');
    Route::delete('/community/reactions/{messageId}', [CommunityController::class, 'removeReaction'])->name('community.remove_reaction');
    Route::get('/community/reactions/{messageId}', [CommunityController::class, 'getReactions'])->name('community.get_reactions');
    
    Route::post('/community/comments', [CommunityController::class, 'addComment'])->name('community.add_comment');
    Route::get('/community/comments', [CommunityController::class, 'loadComments'])->name('community.load_comments');
    Route::delete('/community/comments/{commentId}', [CommunityController::class, 'deleteComment'])->name('community.delete_comment');
    Route::put('/community/comments/{commentId}', [CommunityController::class, 'editComment'])->name('community.edit_comment');
    
    // Permission System Routes
    Route::get('/community/permissions', [CommunityController::class, 'getUserGroupPermissions'])->name('community.get_permissions');
    
    // File Management Routes
    Route::delete('/community/attachments/{messageId}', [CommunityController::class, 'deleteAttachment'])->name('community.delete_attachment');
    Route::get('/community/attachments/{messageId}/download', [CommunityController::class, 'downloadAttachment'])->name('community.download_attachment');
});

// ===================================================================
// CONTENT SUBMISSION ROUTES (User Routes)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // User Submission Management
    Route::get('/submissions', [ContentSubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/create', [ContentSubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/submissions', [ContentSubmissionController::class, 'store'])->name('submissions.store');
    Route::get('/submissions/{submission}', [ContentSubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/submissions/{submission}/edit', [ContentSubmissionController::class, 'edit'])->name('submissions.edit');
    Route::put('/submissions/{submission}', [ContentSubmissionController::class, 'update'])->name('submissions.update');
    Route::delete('/submissions/{submission}', [ContentSubmissionController::class, 'destroy'])->name('submissions.destroy');
    Route::get('/submissions/{submission}/download', [ContentSubmissionController::class, 'downloadAttachment'])->name('submissions.download');
    
    // API Routes
    Route::get('/api/submissions/stats', [ContentSubmissionController::class, 'getStats'])->name('api.submissions.stats');
});

// ===================================================================
// PAYMENT ROUTES (User Routes)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // User Payment Management
    Route::get('/payments/{submission}/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments/{submission}', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    Route::get('/payments/{payment}/download-proof', [PaymentController::class, 'downloadProof'])->name('payments.download_proof');
    
    // User payment history & utilities
    Route::get('/payments/user/history', [PaymentController::class, 'userPayments'])->name('payments.user_history');
});

// ===================================================================
// ✅ ENHANCED ADMIN ROUTES - WITH IMPROVED MIDDLEWARE & ERROR HANDLING
// ===================================================================

// ✅ IMPROVED: Admin middleware with fallback
Route::middleware(['auth'])->group(function () {
    
    // ✅ ENHANCED: Admin middleware with better error handling
    Route::group([
        'middleware' => function ($request, $next) {
            $user = auth()->user();
            
            // Check if user has moderator privileges
            if (!$user || !$user->hasModeratorPrivileges()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthorized access'], 403);
                }
                abort(403, 'Unauthorized access - Admin privileges required');
            }
            
            return $next($request);
        },
        'prefix' => 'admin',
        'as' => 'admin.'
    ], function () {
        
        // ✅ ADMIN DASHBOARD - EXPLICIT ROUTES
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
        Route::post('/dashboard/bulk-action', [DashboardController::class, 'bulkAction'])->name('dashboard.bulk_action');
        Route::get('/dashboard/export', [DashboardController::class, 'exportData'])->name('dashboard.export');
        
        // ✅ ENHANCED SUBMISSION MANAGEMENT - COMPLETE & IMPROVED ROUTES
        Route::get('/submissions', [ContentSubmissionController::class, 'adminIndex'])->name('submissions.index');
        
        // ✅ Core submission actions with better error handling
        Route::post('/submissions/{submission}/approve', [ContentSubmissionController::class, 'approve'])->name('submissions.approve');
        Route::post('/submissions/{submission}/reject', [ContentSubmissionController::class, 'reject'])->name('submissions.reject');
        Route::post('/submissions/{submission}/publish', [ContentSubmissionController::class, 'publish'])->name('submissions.publish');
        
        // ✅ ENHANCED: Admin submission management routes
        Route::get('/submissions/{submission}/edit', [ContentSubmissionController::class, 'adminEdit'])->name('submissions.edit');
        Route::put('/submissions/{submission}/admin-update', [ContentSubmissionController::class, 'adminUpdate'])->name('submissions.admin_update');
        Route::post('/submissions/{submission}/republish', [ContentSubmissionController::class, 'republish'])->name('submissions.republish');
        
        // ✅ ENHANCED: Bulk and utility routes
        Route::post('/submissions/bulk-action', [ContentSubmissionController::class, 'bulkAction'])->name('submissions.bulk_action');
        Route::get('/submissions/export', [ContentSubmissionController::class, 'export'])->name('submissions.export');
        
        // ✅ ENHANCED: Additional submission utilities
        Route::get('/submissions/{submission}/preview', [ContentSubmissionController::class, 'preview'])->name('submissions.preview');
        Route::post('/submissions/{submission}/duplicate', [ContentSubmissionController::class, 'duplicate'])->name('submissions.duplicate');
        
        // ✅ PAYMENT MANAGEMENT - EXPLICIT ROUTES
        Route::get('/payments', [PaymentController::class, 'adminIndex'])->name('payments.index');
        Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        Route::post('/payments/bulk-action', [PaymentController::class, 'bulkAction'])->name('payments.bulk_action');
        Route::get('/payments/export', [PaymentController::class, 'exportCsv'])->name('payments.export');
        
        // ✅ ALUMNI APPROVAL - EXPLICIT ROUTES (Admin only)
        Route::group([
            'middleware' => function ($request, $next) {
                if (!auth()->user()->isAdmin()) {
                    if ($request->expectsJson()) {
                        return response()->json(['error' => 'Admin access required'], 403);
                    }
                    abort(403, 'Admin access required');
                }
                return $next($request);
            }
        ], function () {
            Route::get('/alumni-approval', [AlumniApprovalController::class, 'index'])->name('alumni-approval.index');
            Route::get('/alumni-approval/{id}', [AlumniApprovalController::class, 'show'])->name('alumni-approval.show');
            Route::post('/alumni-approval/{id}/approve', [AlumniApprovalController::class, 'approve'])->name('alumni-approval.approve');
            Route::post('/alumni-approval/{id}/reject', [AlumniApprovalController::class, 'reject'])->name('alumni-approval.reject');
            Route::get('/alumni-approval/{id}/download', [AlumniApprovalController::class, 'downloadDocument'])->name('alumni-approval.download');
            Route::post('/alumni-approval/refresh-cache', [AlumniApprovalController::class, 'refreshCache'])->name('alumni-approval.refresh-cache');
            Route::get('/alumni-approval/stats', [AlumniApprovalController::class, 'getStats'])->name('alumni-approval.stats');
        });
        
        // ✅ API ROUTES FOR DASHBOARD & ANALYTICS
        Route::get('/api/payments/stats', [PaymentController::class, 'getStats'])->name('api.payments.stats');
        Route::get('/api/payments/stats-by-date', [PaymentController::class, 'getStatsByDateRange'])->name('api.payments.stats_by_date');
        Route::get('/api/submissions/stats', [ContentSubmissionController::class, 'getAdminStats'])->name('api.submissions.stats');
        
        // ✅ MODERATOR MANAGEMENT
        Route::get('/moderators', [CommunityController::class, 'getAvailableModerators'])->name('get_moderators');
        Route::post('/groups/{groupId}/assign-moderator', [CommunityController::class, 'assignModerator'])->name('assign_moderator');
        Route::delete('/groups/{groupId}/unassign-moderator', [CommunityController::class, 'unassignModerator'])->name('unassign_moderator');
        
        // ✅ USER ROLE MANAGEMENT (Admin only)
        Route::group([
            'middleware' => function ($request, $next) {
                if (!auth()->user()->isAdmin()) {
                    abort(403, 'Admin access required for user management');
                }
                return $next($request);
            }
        ], function () {
            Route::post('/users/{userId}/promote-to-moderator', [ModeratorController::class, 'promoteToModerator'])->name('promote_moderator');
            Route::post('/users/{userId}/demote-from-moderator', [ModeratorController::class, 'demoteFromModerator'])->name('demote_moderator');
            Route::get('/users/eligible-for-moderation', [ModeratorController::class, 'getEligibleModerators'])->name('eligible_moderators');
        });
        
        // ✅ COMMUNITY MANAGEMENT
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
});

// ===================================================================
// MODERATOR-ONLY ROUTES (Moderator + Admin access)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // ✅ ENHANCED: Moderator middleware with permission check
    Route::group([
        'middleware' => function ($request, $next) {
            $user = auth()->user();
            
            // Check if user has moderator privileges
            if (!$user || !$user->hasModeratorPrivileges()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Moderator access required'], 403);
                }
                abort(403, 'Moderator access required');
            }
            
            return $next($request);
        }
    ], function () {
        
        // Message Moderation (for assigned communities only)
        Route::delete('/community/messages/{messageId}', [CommunityController::class, 'deleteMessage'])->name('community.delete_message');
        Route::put('/community/messages/{messageId}', [CommunityController::class, 'editMessage'])->name('community.edit_message');
        Route::post('/community/messages/{messageId}/pin', [CommunityController::class, 'pinMessage'])->name('community.pin_message');
        Route::delete('/community/messages/{messageId}/pin', [CommunityController::class, 'unpinMessage'])->name('community.unpin_message');
        
        // Moderation Dashboard
        Route::get('/moderator/dashboard', [ModeratorController::class, 'dashboard'])->name('moderator.dashboard');
        Route::get('/moderator/communities', [ModeratorController::class, 'getMyCommunities'])->name('moderator.communities');
        Route::get('/moderator/reports', [ModeratorController::class, 'getReports'])->name('moderator.reports');
        Route::post('/moderator/reports/{reportId}/resolve', [ModeratorController::class, 'resolveReport'])->name('moderator.resolve_report');
        
        // Community Statistics for assigned communities
        Route::get('/moderator/stats/{groupId}', [ModeratorController::class, 'getCommunityStats'])->name('moderator.community_stats');
    });
});

// ===================================================================
// API ROUTES for Frontend Integration
// ===================================================================

Route::prefix('api')->middleware(['auth'])->group(function () {
    
    // User Role Information
    Route::get('/user/role-info', function() {
        try {
            $user = Auth::user();
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'role' => $user->role,
                    'permissions' => [
                        'is_admin' => $user->isAdmin(),
                        'is_moderator' => $user->isModerator(),
                        'can_moderate' => $user->hasModeratorPrivileges(),
                        'can_create_submissions' => method_exists($user, 'canCreateSubmissions') ? $user->canCreateSubmissions() : true,
                        'can_manage_submissions' => method_exists($user, 'canManageSubmissions') ? $user->canManageSubmissions() : $user->isAdmin(),
                        'can_manage_payments' => method_exists($user, 'canManagePayments') ? $user->canManagePayments() : $user->isAdmin(),
                        'can_approve_alumni' => method_exists($user, 'canApproveAlumni') ? $user->canApproveAlumni() : $user->isAdmin(),
                        'can_access_admin_dashboard' => method_exists($user, 'canAccessAdminDashboard') ? $user->canAccessAdminDashboard() : $user->hasModeratorPrivileges(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to get user info'], 500);
        }
    })->name('api.user.role_info');
    
    // Communities with Permission Info
    Route::get('/communities/with-permissions', function() {
        try {
            $user = Auth::user();
            $communities = App\Models\ChatGroup::where('is_approved', true)
                                               ->with(['creator:id,full_name,role', 'moderator:id,full_name,role'])
                                               ->get()
                                               ->map(function($community) use ($user) {
                                                   return [
                                                       'id' => $community->id,
                                                       'name' => $community->name,
                                                       'description' => $community->description,
                                                       'creator' => $community->creator,
                                                       'moderator' => $community->moderator,
                                                       'permissions' => [
                                                           'can_read' => true,
                                                           'can_post' => $user->hasModeratorPrivileges() || 
                                                                       $community->creator_id === $user->id ||
                                                                       $community->moderator_id === $user->id,
                                                           'can_moderate' => $user->hasModeratorPrivileges() || 
                                                                           $community->creator_id === $user->id ||
                                                                           $community->moderator_id === $user->id,
                                                       ]
                                                   ];
                                               });
            
            return response()->json(['communities' => $communities]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to get communities'], 500);
        }
    })->name('api.communities.with_permissions');
    
    // Payment Methods API
    Route::get('/payment-methods', [PaymentController::class, 'getPaymentMethods'])->name('api.payment_methods');
    
    // Submission Categories API
    Route::get('/submission-categories', function() {
        try {
            if (class_exists('App\Models\SubmissionCategory')) {
                $categories = App\Models\SubmissionCategory::where('is_active', true)->get();
            } else {
                $categories = [];
            }
            return response()->json(['categories' => $categories]);
        } catch (\Exception $e) {
            return response()->json(['categories' => []]);
        }
    })->name('api.submission_categories');
    
    // User Statistics API
    Route::get('/user/stats', function() {
        try {
            $user = Auth::user();
            $stats = [
                'submissions' => method_exists($user, 'getSubmissionStats') ? $user->getSubmissionStats() : [],
                'payments' => method_exists($user, 'getPaymentStats') ? $user->getPaymentStats() : [],
                'notifications' => class_exists('App\Models\Notification') ? 
                    (method_exists('App\Models\Notification', 'getStatsForUser') ? 
                        App\Models\Notification::getStatsForUser($user->id) : []) : [],
            ];
            return response()->json(['stats' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['stats' => []]);
        }
    })->name('api.user.stats');
});

// ===================================================================
// ✅ ADDITIONAL ADMIN ROUTES FOR LEGACY MIDDLEWARE SUPPORT
// ===================================================================

// ✅ FALLBACK: Support for legacy AdminOnlyMiddleware if it exists
if (class_exists('\App\Http\Middleware\AdminOnlyMiddleware')) {
    Route::middleware(['auth', \App\Http\Middleware\AdminOnlyMiddleware::class])->prefix('admin')->name('admin.legacy.')->group(function () {
        // Legacy admin routes fallback
        Route::get('/submissions-legacy', [ContentSubmissionController::class, 'adminIndex'])->name('submissions.index');
        Route::post('/submissions-legacy/{submission}/approve', [ContentSubmissionController::class, 'approve'])->name('submissions.approve');
        Route::post('/submissions-legacy/{submission}/reject', [ContentSubmissionController::class, 'reject'])->name('submissions.reject');
        Route::post('/submissions-legacy/{submission}/publish', [ContentSubmissionController::class, 'publish'])->name('submissions.publish');
    });
}

// ===================================================================
// DEBUG ROUTES (Development Only)
// ===================================================================

if (app()->environment('local')) {
    Route::middleware(['auth'])->group(function () {
        
        // User Roles Debug
        Route::get('/debug/user-roles', function() {
            try {
                $users = App\Models\User::select('id', 'full_name', 'role', 'is_verified')
                                        ->limit(20)
                                        ->get()
                                        ->map(function($user) {
                                            return [
                                                'id' => $user->id,
                                                'name' => $user->full_name,
                                                'role' => $user->role,
                                                'is_admin' => $user->isAdmin(),
                                                'is_moderator' => $user->isModerator(),
                                                'has_moderator_privileges' => $user->hasModeratorPrivileges(),
                                            ];
                                        });
                
                return response()->json([
                    'users' => $users,
                    'role_distribution' => [
                        'admin' => App\Models\User::where('role', 'admin')->count(),
                        'moderator' => App\Models\User::where('role', 'moderator')->count(),
                        'mahasiswa' => App\Models\User::where('role', 'mahasiswa')->count(),
                        'alumni' => App\Models\User::where('role', 'alumni')->count(),
                        'tenaga_pendidik' => App\Models\User::where('role', 'tenaga_pendidik')->count(),
                    ]
                ], 200, [], JSON_PRETTY_PRINT);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        })->name('debug.user_roles');
        
        // Alumni Pending Debug
        Route::get('/debug/alumni-pending', function() {
            try {
                $pendingAlumni = App\Models\User::where('role', 'alumni')
                    ->where('is_verified', false)
                    ->select('id', 'full_name', 'email', 'verification_doc_path', 'created_at')
                    ->limit(10)
                    ->get();
                
                return response()->json([
                    'total_pending' => $pendingAlumni->count(),
                    'pending_alumni' => $pendingAlumni,
                    'routes' => [
                        'admin_approval_index' => route('admin.alumni-approval.index'),
                        'sample_detail' => $pendingAlumni->count() > 0 ? 
                            route('admin.alumni-approval.show', $pendingAlumni->first()->id) : null
                    ]
                ], 200, [], JSON_PRETTY_PRINT);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        })->name('debug.alumni_pending');
        
        // ✅ ENHANCED: Test admin permissions
        Route::get('/debug/admin-permissions', function() {
            try {
                $user = auth()->user();
                return response()->json([
                    'user_info' => [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'role' => $user->role,
                    ],
                    'permissions' => [
                        'isAdmin' => $user->isAdmin(),
                        'isModerator' => $user->isModerator(),
                        'hasModeratorPrivileges' => $user->hasModeratorPrivileges(),
                        'canManageSubmissions' => $user->canManageSubmissions(),
                        'canManagePayments' => $user->canManagePayments(),
                        'canApproveAlumni' => $user->canApproveAlumni(),
                        'canAccessAdminDashboard' => $user->canAccessAdminDashboard(),
                    ],
                    'available_admin_routes' => [
                        'dashboard' => route('admin.dashboard.index'),
                        'submissions' => route('admin.submissions.index'),
                        'payments' => route('admin.payments.index'),
                        'alumni_approval' => route('admin.alumni-approval.index'),
                    ]
                ], 200, [], JSON_PRETTY_PRINT);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        })->name('debug.admin_permissions');
        
        // Test Routes
        Route::get('/debug/test-routes', function() {
            $routes = [
                'Admin Dashboard' => [
                    'GET /admin/dashboard' => route('admin.dashboard.index'),
                ],
                'Alumni Approval' => [
                    'GET /admin/alumni-approval' => route('admin.alumni-approval.index'),
                ],
                'Admin Payments' => [
                    'GET /admin/payments' => route('admin.payments.index'),
                ],
                'Admin Submissions' => [
                    'GET /admin/submissions' => route('admin.submissions.index'),
                ],
                'Community' => [
                    'GET /community' => route('community'),
                ],
                'API Routes' => [
                    'GET /api/user/role-info' => route('api.user.role_info'),
                ],
                'Enhanced Admin Submission Routes' => [
                    'GET /admin/submissions/{id}/edit' => 'admin.submissions.edit',
                    'PUT /admin/submissions/{id}/admin-update' => 'admin.submissions.admin_update',
                    'POST /admin/submissions/{id}/republish' => 'admin.submissions.republish',
                    'POST /admin/submissions/bulk-action' => 'admin.submissions.bulk_action',
                    'GET /admin/submissions/export' => 'admin.submissions.export',
                    'GET /admin/submissions/{id}/preview' => 'admin.submissions.preview',
                    'POST /admin/submissions/{id}/duplicate' => 'admin.submissions.duplicate',
                ],
            ];
            
            return response()->json([
                'status' => 'success',
                'available_routes' => $routes,
                'middleware_info' => [
                    'admin_middleware_type' => 'Custom closure (hasModeratorPrivileges)',
                    'moderator_middleware_type' => 'Custom closure (hasModeratorPrivileges)',
                    'legacy_support' => class_exists('\App\Http\Middleware\AdminOnlyMiddleware') ? 'Available' : 'Not available'
                ]
            ], 200, [], JSON_PRETTY_PRINT);
        })->name('debug.test_routes');
        
        // ✅ NEW: Test admin submission routes specifically
        Route::get('/debug/test-submission-routes', function() {
            try {
                $testRoutes = [
                    'submissions_index' => route('admin.submissions.index'),
                    'submissions_export' => route('admin.submissions.export'),
                    'submissions_bulk_action' => route('admin.submissions.bulk_action'),
                ];
                
                // Test if we can create sample route URLs
                if (class_exists('App\Models\ContentSubmission')) {
                    $submission = App\Models\ContentSubmission::first();
                    if ($submission) {
                        $testRoutes['edit_submission'] = route('admin.submissions.edit', $submission->id);
                        $testRoutes['admin_update_submission'] = route('admin.submissions.admin_update', $submission->id);
                        $testRoutes['republish_submission'] = route('admin.submissions.republish', $submission->id);
                        $testRoutes['preview_submission'] = route('admin.submissions.preview', $submission->id);
                        $testRoutes['duplicate_submission'] = route('admin.submissions.duplicate', $submission->id);
                    }
                }
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Admin submission routes tersedia',
                    'test_routes' => $testRoutes,
                    'available_methods' => [
                        'GET /admin/submissions' => 'adminIndex',
                        'GET /admin/submissions/{id}/edit' => 'adminEdit',
                        'PUT /admin/submissions/{id}/admin-update' => 'adminUpdate',
                        'POST /admin/submissions/{id}/republish' => 'republish',
                        'POST /admin/submissions/bulk-action' => 'bulkAction',
                        'GET /admin/submissions/export' => 'export',
                        'GET /admin/submissions/{id}/preview' => 'preview',
                        'POST /admin/submissions/{id}/duplicate' => 'duplicate',
                    ],
                    'middleware_status' => [
                        'custom_admin_middleware' => 'Active',
                        'permission_check' => 'hasModeratorPrivileges()',
                        'error_handling' => 'JSON + Web support'
                    ]
                ], 200, [], JSON_PRETTY_PRINT);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error testing admin submission routes: ' . $e->getMessage()
                ]);
            }
        })->name('admin.test.submission_routes');
        
        // ✅ NEW: Test specific admin access
        Route::get('/debug/test-admin-access', function() {
            try {
                $user = auth()->user();
                
                // Test middleware logic
                $canAccess = $user && $user->hasModeratorPrivileges();
                
                return response()->json([
                    'status' => $canAccess ? 'success' : 'failed',
                    'message' => $canAccess ? 'Admin access granted' : 'Admin access denied',
                    'user_details' => [
                        'id' => $user->id,
                        'role' => $user->role,
                        'has_moderator_privileges' => $user->hasModeratorPrivileges(),
                        'is_admin' => $user->isAdmin(),
                        'is_moderator' => $user->isModerator(),
                    ],
                    'test_urls' => $canAccess ? [
                        'submissions_index' => url('/admin/submissions'),
                        'dashboard_index' => url('/admin/dashboard'),
                        'payments_index' => url('/admin/payments'),
                    ] : null
                ], 200, [], JSON_PRETTY_PRINT);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error testing admin access: ' . $e->getMessage()
                ]);
            }
        })->name('debug.test_admin_access');
    });
}