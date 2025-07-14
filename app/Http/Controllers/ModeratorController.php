<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ChatGroup;
use App\Models\GroupMessage;
use App\Models\PostReaction;
use App\Models\PostComment;

class ModeratorController extends Controller
{
    /**
     * ✅ Moderator Dashboard - Overview of assigned communities
     */
    public function dashboard(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            // Verify user has moderator privileges
            if (!$currentUser->hasModeratorPrivileges()) {
                return redirect()->route('home')->with('error', 'Akses ditolak. Anda bukan moderator.');
            }

            // Get moderated communities
            $moderatedCommunities = $currentUser->getAllModeratedCommunities();
            
            // Calculate stats for each community
            $communityStats = $moderatedCommunities->map(function($community) {
                $stats = $this->getCommunityStatistics($community->id);
                return [
                    'community' => $community,
                    'stats' => $stats
                ];
            });

            // Overall moderator stats
            $overallStats = [
                'total_communities' => $moderatedCommunities->count(),
                'total_messages' => GroupMessage::whereIn('group_id', $moderatedCommunities->pluck('id'))->count(),
                'messages_today' => GroupMessage::whereIn('group_id', $moderatedCommunities->pluck('id'))
                                                ->whereDate('created_at', today())->count(),
                'pending_reports' => 0, // TODO: Implement reports system
                'active_users' => GroupMessage::whereIn('group_id', $moderatedCommunities->pluck('id'))
                                              ->distinct('sender_id')
                                              ->where('created_at', '>=', now()->subWeek())
                                              ->count()
            ];

            return view('moderator.dashboard', compact('communityStats', 'overallStats', 'currentUser'));

        } catch (\Exception $e) {
            Log::error('Error loading moderator dashboard', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')->with('error', 'Terjadi kesalahan saat memuat dashboard moderator.');
        }
    }

    /**
     * ✅ Get communities managed by current moderator
     */
    public function getMyCommunities(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser->hasModeratorPrivileges()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $communities = $currentUser->getAllModeratedCommunities()
                                      ->map(function($community) {
                                          return [
                                              'id' => $community->id,
                                              'name' => $community->name,
                                              'description' => $community->description,
                                              'is_approved' => $community->is_approved,
                                              'creator' => $community->creator,
                                              'moderator' => $community->moderator,
                                              'stats' => $this->getCommunityStatistics($community->id),
                                              'permissions' => [
                                                  'can_post' => true,
                                                  'can_moderate' => true,
                                                  'can_edit_messages' => true,
                                                  'can_delete_messages' => true
                                              ]
                                          ];
                                      });

            return response()->json([
                'status' => 'success',
                'communities' => $communities
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting moderator communities', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving communities'
            ], 500);
        }
    }

    /**
     * ✅ Get detailed statistics for a specific community
     */
    public function getCommunityStats(Request $request, $groupId)
    {
        try {
            $currentUser = Auth::user();
            
            // Verify moderator can access this community
            if (!$currentUser->canModerateCommunity($groupId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke komunitas ini'
                ], 403);
            }

            $community = ChatGroup::with(['creator', 'moderator'])->find($groupId);
            
            if (!$community) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Community not found'
                ], 404);
            }

            $stats = $this->getCommunityStatistics($groupId);
            $recentActivity = $this->getRecentActivity($groupId);
            $topContributors = $this->getTopContributors($groupId);

            return response()->json([
                'status' => 'success',
                'community' => [
                    'id' => $community->id,
                    'name' => $community->name,
                    'description' => $community->description,
                    'creator' => $community->creator,
                    'moderator' => $community->moderator,
                    'created_at' => $community->created_at
                ],
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'top_contributors' => $topContributors
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting community stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'group_id' => $groupId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving community statistics'
            ], 500);
        }
    }

    /**
     * ✅ Get reports for moderated communities
     */
    public function getReports(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser->hasModeratorPrivileges()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // TODO: Implement proper reports system
            // For now, return empty array
            $reports = [];

            return response()->json([
                'status' => 'success',
                'reports' => $reports,
                'message' => 'Sistem laporan akan segera tersedia'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting reports', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving reports'
            ], 500);
        }
    }

    /**
     * ✅ Resolve a report (placeholder)
     */
    public function resolveReport(Request $request, $reportId)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser->hasModeratorPrivileges()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // TODO: Implement proper report resolution
            Log::info('Report resolution attempted', [
                'moderator_id' => $currentUser->id,
                'report_id' => $reportId
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan berhasil diselesaikan'
            ]);

        } catch (\Exception $e) {
            Log::error('Error resolving report', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'report_id' => $reportId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error resolving report'
            ], 500);
        }
    }

    /**
     * ✅ Promote user to moderator (Admin only)
     */
    public function promoteToModerator(Request $request, $userId)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya admin yang dapat mempromosikan moderator'
                ], 403);
            }

            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            if ($user->hasModeratorPrivileges()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User sudah memiliki privilege moderator'
                ], 422);
            }

            $oldRole = $user->role;
            $user->role = 'moderator';
            $user->save();

            Log::info('User promoted to moderator', [
                'admin_id' => $currentUser->id,
                'user_id' => $userId,
                'user_name' => $user->full_name,
                'old_role' => $oldRole,
                'new_role' => 'moderator'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "{$user->full_name} berhasil dipromosikan menjadi moderator",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'old_role' => $oldRole,
                    'new_role' => $user->role,
                    'role_display' => $user->role_display
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error promoting user to moderator', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id(),
                'user_id' => $userId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mempromosikan user'
            ], 500);
        }
    }

    /**
     * ✅ Demote moderator to regular user (Admin only)
     */
    public function demoteFromModerator(Request $request, $userId)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya admin yang dapat menurunkan moderator'
                ], 403);
            }

            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            if (!$user->isModerator()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User bukan moderator'
                ], 422);
            }

            // Remove from moderated communities
            ChatGroup::where('moderator_id', $userId)->update(['moderator_id' => null]);

            $oldRole = $user->role;
            $user->role = 'mahasiswa'; // Default demote to mahasiswa
            $user->save();

            Log::info('Moderator demoted', [
                'admin_id' => $currentUser->id,
                'user_id' => $userId,
                'user_name' => $user->full_name,
                'old_role' => $oldRole,
                'new_role' => 'mahasiswa'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "{$user->full_name} tidak lagi menjadi moderator",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'old_role' => $oldRole,
                    'new_role' => $user->role,
                    'role_display' => $user->role_display
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error demoting moderator', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id(),
                'user_id' => $userId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menurunkan moderator'
            ], 500);
        }
    }

    /**
     * ✅ Get users eligible for moderation role
     */
    public function getEligibleModerators(Request $request)
    {
        try {
            $currentUser = Auth::user();
            
            if (!$currentUser->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Get active users who are not already moderators/admins
            $eligibleUsers = User::whereIn('role', ['mahasiswa', 'alumni', 'tenaga_pendidik'])
                                ->where('is_verified', true)
                                ->withCount([
                                    'sentMessages as message_count',
                                    'createdCommunities as created_communities_count'
                                ])
                                ->having('message_count', '>', 10) // At least 10 messages
                                ->orderBy('message_count', 'desc')
                                ->limit(50)
                                ->get()
                                ->map(function($user) {
                                    return [
                                        'id' => $user->id,
                                        'name' => $user->full_name,
                                        'role' => $user->role,
                                        'role_display' => $user->role_display,
                                        'email' => $user->email,
                                        'prodi' => $user->prodi,
                                        'fakultas' => $user->fakultas,
                                        'message_count' => $user->message_count,
                                        'created_communities' => $user->created_communities_count,
                                        'profile_picture' => $user->profile_picture,
                                        'eligibility_score' => $this->calculateEligibilityScore($user)
                                    ];
                                });

            return response()->json([
                'status' => 'success',
                'eligible_users' => $eligibleUsers
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting eligible moderators', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving eligible users'
            ], 500);
        }
    }

    // ===============================================
    // ✅ PRIVATE HELPER METHODS
    // ===============================================

    /**
     * Get comprehensive statistics for a community
     */
    private function getCommunityStatistics($groupId)
    {
        $totalMessages = GroupMessage::where('group_id', $groupId)->count();
        $messagesThisWeek = GroupMessage::where('group_id', $groupId)
                                       ->where('created_at', '>=', now()->subWeek())
                                       ->count();
        $messagesThisMonth = GroupMessage::where('group_id', $groupId)
                                        ->where('created_at', '>=', now()->subMonth())
                                        ->count();
        $uniquePosters = GroupMessage::where('group_id', $groupId)
                                    ->distinct('sender_id')
                                    ->count();
        
        $totalReactions = PostReaction::whereIn('message_id', 
            GroupMessage::where('group_id', $groupId)->pluck('id')
        )->count();
        
        $totalComments = PostComment::whereIn('message_id',
            GroupMessage::where('group_id', $groupId)->pluck('id')
        )->count();

        $lastActivity = GroupMessage::where('group_id', $groupId)
                                   ->latest()
                                   ->first();

        return [
            'total_messages' => $totalMessages,
            'messages_this_week' => $messagesThisWeek,
            'messages_this_month' => $messagesThisMonth,
            'unique_posters' => $uniquePosters,
            'total_reactions' => $totalReactions,
            'total_comments' => $totalComments,
            'last_activity' => $lastActivity ? $lastActivity->created_at : null,
            'engagement_rate' => $totalMessages > 0 ? round(($totalReactions + $totalComments) / $totalMessages * 100, 2) : 0,
            'activity_level' => $this->calculateActivityLevel($messagesThisWeek)
        ];
    }

    /**
     * Get recent activity for a community
     */
    private function getRecentActivity($groupId)
    {
        return GroupMessage::where('group_id', $groupId)
                          ->with('sender:id,full_name,role')
                          ->latest()
                          ->limit(10)
                          ->get()
                          ->map(function($message) {
                              return [
                                  'id' => $message->id,
                                  'content' => Str::limit($message->message_content, 100),
                                  'sender' => $message->sender,
                                  'created_at' => $message->created_at,
                                  'time_ago' => $message->created_at->diffForHumans()
                              ];
                          });
    }

    /**
     * Get top contributors for a community
     */
    private function getTopContributors($groupId)
    {
        return GroupMessage::where('group_id', $groupId)
                          ->select('sender_id', DB::raw('count(*) as message_count'))
                          ->with('sender:id,full_name,role,profile_picture')
                          ->groupBy('sender_id')
                          ->orderBy('message_count', 'desc')
                          ->limit(10)
                          ->get()
                          ->map(function($item) {
                              return [
                                  'user' => $item->sender,
                                  'message_count' => $item->message_count,
                                  'contribution_percentage' => 0 // Calculate this based on total messages
                              ];
                          });
    }

    /**
     * Calculate activity level based on weekly messages
     */
    private function calculateActivityLevel($weeklyMessages)
    {
        if ($weeklyMessages >= 50) {
            return 'very_high';
        } elseif ($weeklyMessages >= 20) {
            return 'high';
        } elseif ($weeklyMessages >= 5) {
            return 'medium';
        } elseif ($weeklyMessages >= 1) {
            return 'low';
        } else {
            return 'inactive';
        }
    }

    /**
     * Calculate eligibility score for potential moderators
     */
    private function calculateEligibilityScore($user)
    {
        $score = 0;
        
        // Message activity (max 40 points)
        $score += min($user->message_count * 2, 40);
        
        // Created communities (max 20 points)
        $score += min($user->created_communities_count * 10, 20);
        
        // Role bonus (max 20 points)
        if ($user->role === 'tenaga_pendidik') {
            $score += 20;
        } elseif ($user->role === 'alumni') {
            $score += 15;
        } elseif ($user->role === 'mahasiswa') {
            $score += 10;
        }
        
        // Verification bonus (max 20 points)
        if ($user->is_verified) {
            $score += 20;
        }
        
        return min($score, 100); // Cap at 100
    }
}