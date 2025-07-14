<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; // ✅ PENTING: Tambahkan import Auth
use Illuminate\Support\Facades\Schema; // ✅ PENTING: Tambahkan import Schema
use Illuminate\Support\Facades\DB; // ✅ PENTING: Tambahkan import DB
use Illuminate\Support\Facades\Hash; // ✅ PENTING: Tambahkan import Hash
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ModeratorController;
use App\Http\Controllers\ContentSubmissionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\AlumniApprovalController; // ✅ Import sudah benar

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
    
    // ✅ BASIC COMMUNITY ROUTES (Enhanced with permission checking)
    Route::get('/community', [CommunityController::class, 'showCommunityChatPage'])->name('community');
    Route::post('/community/send-message', [CommunityController::class, 'sendGroupMessage'])->name('community.send_message');
    Route::get('/community/messages', [CommunityController::class, 'getMessages'])->name('community.get_messages');
    Route::get('/community/stats', [CommunityController::class, 'getStats'])->name('community.stats');
    
    // ✅ REACTIONS & COMMENTS SYSTEM
    Route::post('/community/reactions', [CommunityController::class, 'addReaction'])->name('community.add_reaction');
    Route::delete('/community/reactions/{messageId}', [CommunityController::class, 'removeReaction'])->name('community.remove_reaction');
    Route::get('/community/reactions/{messageId}', [CommunityController::class, 'getReactions'])->name('community.get_reactions');
    
    Route::post('/community/comments', [CommunityController::class, 'addComment'])->name('community.add_comment');
    Route::get('/community/comments', [CommunityController::class, 'loadComments'])->name('community.load_comments');
    Route::delete('/community/comments/{commentId}', [CommunityController::class, 'deleteComment'])->name('community.delete_comment');
    Route::put('/community/comments/{commentId}', [CommunityController::class, 'editComment'])->name('community.edit_comment');
    
    // ✅ PERMISSION SYSTEM ROUTES
    Route::get('/community/permissions', [CommunityController::class, 'getUserGroupPermissions'])->name('community.get_permissions');
    
    // ✅ FILE MANAGEMENT ROUTES
    Route::delete('/community/attachments/{messageId}', [CommunityController::class, 'deleteAttachment'])->name('community.delete_attachment');
    Route::get('/community/attachments/{messageId}/download', [CommunityController::class, 'downloadAttachment'])->name('community.download_attachment');
});

// ===================================================================
// ✅ CONTENT SUBMISSION ROUTES (User Routes)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // ✅ USER SUBMISSION MANAGEMENT
    Route::get('/submissions', [ContentSubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/create', [ContentSubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/submissions', [ContentSubmissionController::class, 'store'])->name('submissions.store');
    Route::get('/submissions/{submission}', [ContentSubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/submissions/{submission}/edit', [ContentSubmissionController::class, 'edit'])->name('submissions.edit');
    Route::put('/submissions/{submission}', [ContentSubmissionController::class, 'update'])->name('submissions.update');
    Route::delete('/submissions/{submission}', [ContentSubmissionController::class, 'destroy'])->name('submissions.destroy');
    Route::get('/submissions/{submission}/download', [ContentSubmissionController::class, 'downloadAttachment'])->name('submissions.download');
    
    // ✅ API ROUTES
    Route::get('/api/submissions/stats', [ContentSubmissionController::class, 'getStats'])->name('api.submissions.stats');
});

// ===================================================================
// ✅ PAYMENT ROUTES (User Routes)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // ✅ USER PAYMENT MANAGEMENT
    Route::get('/payments/{submission}/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments/{submission}', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    Route::get('/payments/{payment}/download-proof', [PaymentController::class, 'downloadProof'])->name('payments.download_proof');
    
    // ✅ ADDED: User payment history & utilities
    Route::get('/payments/user/history', [PaymentController::class, 'userPayments'])->name('payments.user_history');
});

// ===================================================================
// ✅ ADMIN ROUTES FOR CONTENT SUBMISSION & PAYMENT MANAGEMENT
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // ✅ ADMIN SUBMISSION MANAGEMENT
    Route::get('/admin/submissions', [ContentSubmissionController::class, 'adminIndex'])->name('admin.submissions.index');
    Route::post('/admin/submissions/{submission}/approve', [ContentSubmissionController::class, 'approve'])->name('admin.submissions.approve');
    Route::post('/admin/submissions/{submission}/reject', [ContentSubmissionController::class, 'reject'])->name('admin.submissions.reject');
    // ✅ ADDED: Missing publish route (method exists in controller but route was missing)
    Route::post('/admin/submissions/{submission}/publish', [ContentSubmissionController::class, 'publish'])->name('admin.submissions.publish');
    
    // ✅ ADMIN PAYMENT MANAGEMENT  
    Route::get('/admin/payments', [PaymentController::class, 'adminIndex'])->name('admin.payments.index');
    Route::post('/admin/payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('admin.payments.confirm');
    Route::post('/admin/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('admin.payments.reject');
    Route::post('/admin/payments/bulk-action', [PaymentController::class, 'bulkAction'])->name('admin.payments.bulk_action');
    
    // ✅ ADDED: Export & Analytics Routes
    Route::get('/admin/payments/export', [PaymentController::class, 'exportCsv'])->name('admin.payments.export');
    
    // ✅ ADMIN API ROUTES
    Route::get('/api/admin/payments/stats', [PaymentController::class, 'getStats'])->name('api.admin.payments.stats');
    Route::get('/api/admin/payments/stats-by-date', [PaymentController::class, 'getStatsByDateRange'])->name('api.admin.payments.stats_by_date');
});

// ===================================================================
// ✅ ADMIN ALUMNI APPROVAL ROUTES - FIXED & ORGANIZED
// ===================================================================

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    
    // ✅ Alumni Approval Routes - FIXED
    Route::get('/alumni-approval', [AlumniApprovalController::class, 'index'])
        ->name('alumni-approval.index');
    
    Route::get('/alumni-approval/{id}', [AlumniApprovalController::class, 'show'])
        ->name('alumni-approval.show');
    
    Route::post('/alumni-approval/{id}/approve', [AlumniApprovalController::class, 'approve'])
        ->name('alumni-approval.approve');
    
    Route::post('/alumni-approval/{id}/reject', [AlumniApprovalController::class, 'reject'])
        ->name('alumni-approval.reject');
    
    Route::get('/alumni-approval/{id}/download', [AlumniApprovalController::class, 'downloadDocument'])
        ->name('alumni-approval.download');
    
    // ✅ MODERATOR MANAGEMENT - Sementara tanpa middleware khusus, akan divalidasi di controller
    Route::get('/moderators', [CommunityController::class, 'getAvailableModerators'])->name('get_moderators');
    Route::post('/groups/{groupId}/assign-moderator', [CommunityController::class, 'assignModerator'])->name('assign_moderator');
    Route::delete('/groups/{groupId}/unassign-moderator', [CommunityController::class, 'unassignModerator'])->name('unassign_moderator');
    
    // ✅ USER ROLE MANAGEMENT
    Route::post('/users/{userId}/promote-to-moderator', [ModeratorController::class, 'promoteToModerator'])->name('promote_moderator');
    Route::post('/users/{userId}/demote-from-moderator', [ModeratorController::class, 'demoteFromModerator'])->name('demote_moderator');
    Route::get('/users/eligible-for-moderation', [ModeratorController::class, 'getEligibleModerators'])->name('eligible_moderators');
    
    // ✅ COMMUNITY MANAGEMENT (yang sudah ada)
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
// ✅ MODERATOR-ONLY ROUTES (Moderator + Admin access)
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    // ✅ MESSAGE MODERATION (for assigned communities only)
    Route::delete('/community/messages/{messageId}', [CommunityController::class, 'deleteMessage'])->name('community.delete_message');
    Route::put('/community/messages/{messageId}', [CommunityController::class, 'editMessage'])->name('community.edit_message');
    Route::post('/community/messages/{messageId}/pin', [CommunityController::class, 'pinMessage'])->name('community.pin_message');
    Route::delete('/community/messages/{messageId}/pin', [CommunityController::class, 'unpinMessage'])->name('community.unpin_message');
    
    // ✅ MODERATION DASHBOARD - Sementara tanpa middleware khusus
    Route::get('/moderator/dashboard', [ModeratorController::class, 'dashboard'])->name('moderator.dashboard');
    Route::get('/moderator/communities', [ModeratorController::class, 'getMyCommunities'])->name('moderator.communities');
    Route::get('/moderator/reports', [ModeratorController::class, 'getReports'])->name('moderator.reports');
    Route::post('/moderator/reports/{reportId}/resolve', [ModeratorController::class, 'resolveReport'])->name('moderator.resolve_report');
    
    // ✅ COMMUNITY STATISTICS for assigned communities
    Route::get('/moderator/stats/{groupId}', [ModeratorController::class, 'getCommunityStats'])->name('moderator.community_stats');
});

// ===================================================================
// ✅ API ROUTES for Frontend Integration - FIXED
// ===================================================================

Route::prefix('api')->middleware(['auth'])->group(function () {
    
    // ✅ User Role Information - FIXED dengan try-catch
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
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to get user info'], 500);
        }
    })->name('api.user.role_info');
    
    // ✅ Communities with Permission Info - FIXED dengan try-catch
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
    
    // ✅ Payment Methods API
    Route::get('/payment-methods', [PaymentController::class, 'getPaymentMethods'])->name('api.payment_methods');
    
    // ✅ Submission Categories API - FIXED dengan try-catch
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
    
    // ✅ User Statistics API - FIXED dengan try-catch
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
// ✅ ENHANCED DEBUG ROUTES (Development Only) - KEPT BUT SIMPLIFIED
// ===================================================================

if (app()->environment('local')) {
    Route::middleware(['auth'])->group(function () {
        
        // ✅ BASIC DEBUG ROUTES
        Route::get('/debug/user-roles', function() {
            try {
                $users = App\Models\User::select('id', 'full_name', 'role', 'is_verified')
                                        ->limit(20) // Limit untuk safety
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
        
        // ✅ ALUMNI PENDING DEBUG
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
        
        // ✅ TEST ROUTES
        Route::get('/debug/test-routes', function() {
            $routes = [
                'Alumni Approval' => [
                    'GET /admin/alumni-approval' => route('admin.alumni-approval.index'),
                ],
                'Community' => [
                    'GET /community' => route('community'),
                ],
                'API Routes' => [
                    'GET /api/user/role-info' => route('api.user.role_info'),
                ],
            ];
            
            return response()->json([
                'status' => 'success',
                'available_routes' => $routes,
            ], 200, [], JSON_PRETTY_PRINT);
        })->name('debug.test_routes');
        
    });
}