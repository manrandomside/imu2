<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserMatch;
use App\Models\User;
use App\Models\Message; // Ditambahkan: Import model Message

class ChatController extends Controller
{
    /**
     * Menampilkan halaman personal chat dengan daftar match dinamis dan pesan aktual/dummy.
     */
    public function showPersonalChatPage(Request $request)
    {
        $currentUser = Auth::user();

        // Ambil daftar match untuk user yang sedang login
        // Match bisa jadi user1_id atau user2_id
        $myMatches = UserMatch::where(function ($query) use ($currentUser) {
                                $query->where('user1_id', $currentUser->id)
                                      ->orWhere('user2_id', $currentUser->id);
                            })
                            ->with(['user1', 'user2']) // Eager load data user yang match
                            ->get();

        $chatListUsers = [];
        $selectedOtherUser = null;
        $activeMatch = null; // Tambahan: Untuk menyimpan objek match yang aktif

        $activeChatUserId = $request->query('with', null); // Ambil ID user dari query string jika ada (misal: /chat/personal?with=ID_USER)

        // Membangun daftar chat dari matches
        foreach ($myMatches as $match) {
            // Tentukan siapa "other user" dalam match ini
            $otherUserInMatch = ($match->user1_id === $currentUser->id) ? $match->user2 : $match->user1;

            // Pastikan otherUserInMatch tidak null sebelum mengakses propertinya (bisa terjadi jika foreign key tidak valid)
            if ($otherUserInMatch) {
                $chatListUsers[] = (object)[
                    'id' => $otherUserInMatch->id,
                    'full_name' => $otherUserInMatch->full_name,
                    // Menggunakan profile_picture dari otherUser, atau placeholder
                    'profile_picture' => $otherUserInMatch->profile_picture,
                    'last_message' => 'Mulai obrolan!', // Placeholder, nanti dari pesan terakhir
                    'is_active' => ($otherUserInMatch->id == $activeChatUserId), // Tandai jika ini chat yang aktif
                    'match_id' => $match->id // Tambahkan match_id
                ];

                // Jika user ini yang diminta untuk chat aktif
                if ($otherUserInMatch->id == $activeChatUserId) {
                    $selectedOtherUser = $otherUserInMatch;
                    $activeMatch = $match; // Simpan objek match yang aktif
                }
            }
        }

        // Jika tidak ada user spesifik yang diminta via query string,
        // atau jika user yang diminta tidak ada dalam daftar match,
        // pilih user pertama dari daftar match sebagai chat aktif default.
        if (empty($selectedOtherUser) && count($chatListUsers) > 0) {
            // Pastikan ada chatListUsers[0] sebelum mengaksesnya
            if (isset($chatListUsers[0])) {
                $selectedOtherUser = User::find($chatListUsers[0]->id);
                $chatListUsers[0]->is_active = true;
                // Dapatkan juga activeMatch untuk user pertama jika tidak ada query 'with'
                $activeMatch = UserMatch::where(function ($query) use ($currentUser, $selectedOtherUser) {
                                        $query->where('user1_id', $currentUser->id)->where('user2_id', $selectedOtherUser->id);
                                    })->orWhere(function ($query) use ($currentUser, $selectedOtherUser) {
                                        $query->where('user1_id', $selectedOtherUser->id)->where('user2_id', $currentUser->id);
                                    })->first();
            }
        }

        // Ambil pesan aktual dari database jika ada selectedOtherUser dan activeMatch
        $messages = [];
        if (!empty($selectedOtherUser) && !empty($activeMatch)) {
            $messages = Message::where('match_id', $activeMatch->id)
                                ->orderBy('created_at', 'asc') // Urutkan pesan berdasarkan waktu kirim
                                ->get();
        }

        // Kirim semua data ke view
        return view('chat.personal', compact('selectedOtherUser', 'messages', 'currentUser', 'chatListUsers', 'activeMatch'));
    }

    /**
     * Menangani pengiriman pesan baru.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'match_id' => ['required', 'integer', 'exists:matches,id'],
            'message_content' => ['required', 'string', 'max:1000'],
        ], [
            'receiver_id.required' => 'Penerima pesan tidak valid.',
            'match_id.required' => 'Match ID tidak valid.',
            'message_content.required' => 'Pesan tidak boleh kosong.',
            'message_content.max' => 'Pesan terlalu panjang (maksimal 1000 karakter).',
        ]);

        $currentUser = Auth::user();
        $receiverId = $request->receiver_id;
        $matchId = $request->match_id;
        $messageContent = $request->message_content;

        // Pastikan match_id yang diberikan valid untuk user ini
        // dan bahwa user yang sedang login adalah salah satu pihak dalam match tersebut
        $match = UserMatch::where('id', $matchId)
                        ->where(function ($query) use ($currentUser, $receiverId) {
                            $query->where(function ($q) use ($currentUser, $receiverId) {
                                $q->where('user1_id', $currentUser->id)->where('user2_id', $receiverId);
                            })->orWhere(function ($q) use ($currentUser, $receiverId) {
                                $q->where('user1_id', $receiverId)->where('user2_id', $currentUser->id);
                            });
                        })
                        ->first();

        if (!$match) {
            return response()->json(['status' => 'error', 'message' => 'Match tidak valid untuk pengiriman pesan ini.'], 403);
        }

        // Simpan pesan ke database
        $message = Message::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $receiverId,
            'match_id' => $matchId,
            'message_content' => $messageContent,
            'read_at' => null, // Pesan baru belum dibaca
        ]);

        // Untuk real-time chat (opsional), biasanya akan ada broadcast event di sini.
        // Untuk saat ini, kita akan kembalikan pesan yang disimpan agar frontend bisa menampilkannya.

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan berhasil dikirim.',
            'sent_message' => [ // Kembalikan data pesan yang baru dikirim
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'message_content' => $message->message_content,
                'timestamp' => $message->created_at->format('H:i A'), // Format waktu untuk tampilan
            ]
        ], 200);
    }
}
