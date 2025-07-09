<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\ChatGroup; // Ditambahkan: Import model ChatGroup
use App\Models\GroupMessage; // Ditambahkan: Import model GroupMessage
use App\Models\ChatGroupMember; // Ditambahkan: Import model ChatGroupMember

class CommunityController extends Controller
{
    /**
     * Menampilkan halaman community chat dengan data dinamis.
     * ✅ IMPROVED: Better error handling, logging, dan performance optimization
     */
    public function showCommunityChatPage(Request $request)
    {
        try {
            $currentUser = Auth::user();

            // ✅ IMPROVED: Eager loading untuk performance
            $communities = ChatGroup::where('is_approved', true)
                                    ->with('creator:id,full_name,role')
                                    ->orderBy('name', 'asc')
                                    ->get();

            $activeGroupId = $request->query('group', null);
            $selectedGroup = null;
            $groupMessages = collect(); // Koleksi kosong default

            // ✅ IMPROVED: Validasi input group ID
            if ($activeGroupId && is_numeric($activeGroupId)) {
                $selectedGroup = ChatGroup::where('id', $activeGroupId)
                                          ->where('is_approved', true)
                                          ->with('creator:id,full_name,role')
                                          ->first();
                
                // ✅ NEW: Log invalid group access attempt
                if (!$selectedGroup) {
                    Log::warning('User attempted to access invalid community group', [
                        'user_id' => $currentUser->id,
                        'requested_group_id' => $activeGroupId,
                        'ip' => $request->ip()
                    ]);
                }
            }

            // Jika tidak ada grup yang dipilih atau grup tidak valid, pilih grup pertama sebagai default
            if (empty($selectedGroup) && $communities->count() > 0) {
                $selectedGroup = $communities->first();
            }

            // ✅ IMPROVED: Eager loading + pagination untuk performance
            if ($selectedGroup) {
                $groupMessages = GroupMessage::where('group_id', $selectedGroup->id)
                                            ->with(['sender:id,full_name,role,profile_picture']) // Eager load data pengirim
                                            ->orderBy('created_at', 'desc')
                                            ->limit(50) // ✅ NEW: Batasi untuk performance
                                            ->get()
                                            ->reverse(); // ✅ NEW: Reverse agar urutan chronological
            }

            // ✅ NEW: Log successful page view untuk analytics
            Log::info('Community page viewed', [
                'user_id' => $currentUser->id,
                'selected_group_id' => $selectedGroup->id ?? null,
                'total_communities' => $communities->count(),
                'total_messages' => $groupMessages->count()
            ]);

            return view('community.index', compact('communities', 'selectedGroup', 'groupMessages', 'currentUser'));

        } catch (\Exception $e) {
            // ✅ NEW: Comprehensive error logging
            Log::error('Error loading community page', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')->with('error', 'Terjadi kesalahan saat memuat halaman komunitas. Silakan coba lagi.');
        }
    }

    /**
     * Menangani pengiriman pesan baru di grup chat.
     * ✅ COMPLETELY REWRITTEN: Enhanced validation, security, error handling
     */
    public function sendGroupMessage(Request $request)
    {
        try {
            // ✅ IMPROVED: Enhanced validation dengan custom messages
            $validatedData = $request->validate([
                'group_id' => ['required', 'integer', 'exists:chat_groups,id'],
                'message_content' => ['required', 'string', 'min:1', 'max:5000'], // ✅ NEW: min validation
            ], [
                'group_id.required' => 'Grup komunitas tidak valid.',
                'group_id.exists' => 'Grup komunitas tidak ditemukan.',
                'message_content.required' => 'Pesan tidak boleh kosong.',
                'message_content.min' => 'Pesan terlalu pendek.',
                'message_content.max' => 'Pesan terlalu panjang (maksimal 5000 karakter).',
            ]);

            $currentUser = Auth::user();
            $groupId = $validatedData['group_id'];
            $messageContent = trim($validatedData['message_content']); // ✅ NEW: Trim whitespace

            // ✅ IMPROVED: Enhanced group verification
            $group = ChatGroup::where('id', $groupId)
                              ->where('is_approved', true)
                              ->first();

            if (!$group) {
                Log::warning('User attempted to post to non-existent or unapproved group', [
                    'user_id' => $currentUser->id,
                    'group_id' => $groupId,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'status' => 'error', 
                    'message' => 'Grup komunitas tidak ditemukan atau belum disetujui.'
                ], 404);
            }

            // ✅ IMPROVED: Enhanced permission check dengan logging
            $hasPermission = $this->checkPostPermission($currentUser, $group);
            
            if (!$hasPermission) {
                Log::warning('User attempted unauthorized post to community group', [
                    'user_id' => $currentUser->id,
                    'user_role' => $currentUser->role,
                    'group_id' => $groupId,
                    'group_creator_id' => $group->creator_id,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'status' => 'error', 
                    'message' => 'Anda tidak memiliki izin untuk memposting di grup ini. Hanya pembuat grup atau admin yang dapat memposting.'
                ], 403);
            }

            // ✅ NEW: Content filtering untuk keamanan
            $filteredContent = $this->filterContent($messageContent);

            // ✅ IMPROVED: Create message dengan error handling
            $message = GroupMessage::create([
                'group_id' => $groupId,
                'sender_id' => $currentUser->id,
                'message_content' => $filteredContent,
            ]);

            // ✅ NEW: Load sender relationship untuk response
            $message->load('sender:id,full_name,role,profile_picture');

            // ✅ NEW: Log successful message creation
            Log::info('Community message posted successfully', [
                'message_id' => $message->id,
                'user_id' => $currentUser->id,
                'group_id' => $groupId,
                'content_length' => strlen($filteredContent)
            ]);

            // ✅ IMPROVED: Enhanced response dengan sender data yang lengkap
            return response()->json([
                'status' => 'success',
                'message' => 'Pesan berhasil dipublikasikan.',
                'sent_message' => [
                    'id' => $message->id,
                    'group_id' => $message->group_id,
                    'sender_id' => $message->sender_id,
                    'message_content' => $message->message_content,
                    'created_at' => $message->created_at->format('H:i A, d M Y'), // ✅ IMPROVED: Better format
                    'sender' => [
                        'id' => $message->sender->id,
                        'full_name' => $message->sender->full_name,
                        'role' => $message->sender->role,
                        'profile_picture' => $message->sender->profile_picture,
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {
            // ✅ NEW: Handle validation errors specifically
            Log::info('Community message validation failed', [
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
                'request_data' => $request->only(['group_id', 'message_content'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // ✅ NEW: Handle unexpected errors
            Log::error('Error posting community message', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => Auth::id(),
                'request_data' => $request->only(['group_id', 'message_content']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server. Silakan coba lagi dalam beberapa saat.'
            ], 500);
        }
    }

    /**
     * ✅ NEW: Check if user has permission to post in the group
     */
    private function checkPostPermission($user, $group)
    {
        // Admin global dapat posting di semua grup
        if ($user->role === 'admin') {
            return true;
        }

        // Pembuat grup dapat posting
        if ($group->creator_id === $user->id) {
            return true;
        }

        // ✅ FUTURE: Bisa ditambahkan logic untuk moderator grup
        // if ($this->isGroupModerator($user->id, $group->id)) {
        //     return true;
        // }

        return false;
    }

    /**
     * ✅ NEW: Basic content filtering untuk mencegah spam/inappropriate content
     */
    private function filterContent($content)
    {
        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', trim($content));
        
        // Remove null bytes
        $content = str_replace("\0", '', $content);
        
        // Basic HTML entity encoding untuk safety
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8', false);
        
        // ✅ FUTURE: Bisa ditambahkan bad word filtering, spam detection, dll
        
        return $content;
    }

    /**
     * ✅ NEW: API endpoint untuk mengambil pesan terbaru (untuk real-time updates)
     */
    public function getMessages(Request $request)
    {
        try {
            $request->validate([
                'group_id' => ['required', 'integer', 'exists:chat_groups,id'],
                'last_message_id' => ['nullable', 'integer']
            ]);

            $currentUser = Auth::user();
            $groupId = $request->group_id;
            $lastMessageId = $request->last_message_id;

            // Verifikasi bahwa grup exists dan approved
            $group = ChatGroup::where('id', $groupId)
                              ->where('is_approved', true)
                              ->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Grup tidak ditemukan atau tidak dapat diakses.'
                ], 404);
            }

            $query = GroupMessage::where('group_id', $groupId)
                                 ->with('sender:id,full_name,role,profile_picture');
            
            if ($lastMessageId) {
                $query->where('id', '>', $lastMessageId);
            }

            $messages = $query->orderBy('created_at', 'asc')
                             ->limit(50)
                             ->get();

            return response()->json([
                'status' => 'success',
                'messages' => $messages->map(function($message) {
                    return [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'message_content' => $message->message_content,
                        'created_at' => $message->created_at->format('H:i A, d M Y'),
                        'sender' => [
                            'id' => $message->sender->id,
                            'full_name' => $message->sender->full_name,
                            'role' => $message->sender->role,
                            'profile_picture' => $message->sender->profile_picture,
                        ]
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching community messages', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->only(['group_id', 'last_message_id'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil pesan terbaru.'
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get community statistics (untuk admin dashboard - future feature)
     */
    public function getStats()
    {
        try {
            $currentUser = Auth::user();
            
            if ($currentUser->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $stats = [
                'total_communities' => ChatGroup::where('is_approved', true)->count(),
                'pending_communities' => ChatGroup::where('is_approved', false)->count(),
                'total_messages' => GroupMessage::count(),
                'active_users' => GroupMessage::distinct('sender_id')
                                              ->where('created_at', '>=', now()->subDays(30))
                                              ->count(),
                'messages_today' => GroupMessage::whereDate('created_at', today())->count(),
            ];

            return response()->json([
                'status' => 'success',
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching community stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil statistik.'
            ], 500);
        }
    }
}