<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ChatGroup; // Ditambahkan: Import model ChatGroup
use App\Models\GroupMessage; // Ditambahkan: Import model GroupMessage
use App\Models\ChatGroupMember; // Ditambahkan: Import model ChatGroupMember

class CommunityController extends Controller
{
    /**
     * Menampilkan halaman community chat dengan data dinamis.
     */
    public function showCommunityChatPage(Request $request)
    {
        $currentUser = Auth::user();

        // Ambil semua komunitas yang sudah disetujui
        $communities = ChatGroup::where('is_approved', true)
                                ->orderBy('name', 'asc')
                                ->get();

        $activeGroupId = $request->query('group', null); // Ambil ID grup dari query string

        $selectedGroup = null;
        $groupMessages = collect(); // Koleksi kosong default

        // Jika ada ID grup di query string, cari grup tersebut
        if ($activeGroupId) {
            $selectedGroup = ChatGroup::where('id', $activeGroupId)
                                      ->where('is_approved', true)
                                      ->first();
        }

        // Jika tidak ada grup yang dipilih atau grup tidak valid, pilih grup pertama sebagai default
        if (empty($selectedGroup) && $communities->count() > 0) {
            $selectedGroup = $communities->first();
        }

        // Jika ada grup yang dipilih, ambil pesan-pesannya
        if ($selectedGroup) {
            $groupMessages = GroupMessage::where('group_id', $selectedGroup->id)
                                        ->with('sender') // Eager load data pengirim pesan
                                        ->orderBy('created_at', 'asc')
                                        ->get();
        }

        // Kirim semua data ke view
        return view('community.index', compact('communities', 'selectedGroup', 'groupMessages', 'currentUser'));
    }

    /**
     * Menangani pengiriman pesan baru di grup chat.
     */
    public function sendGroupMessage(Request $request)
    {
        $request->validate([
            'group_id' => ['required', 'integer', 'exists:chat_groups,id'],
            'message_content' => ['required', 'string', 'max:5000'], // Batas karakter lebih tinggi untuk post
        ], [
            'group_id.required' => 'Grup tidak valid.',
            'group_id.exists' => 'Grup tidak ditemukan.',
            'message_content.required' => 'Pesan tidak boleh kosong.',
            'message_content.max' => 'Pesan terlalu panjang (maksimal 5000 karakter).',
        ]);

        $currentUser = Auth::user();
        $groupId = $request->group_id;
        $messageContent = $request->message_content;

        // Verifikasi bahwa user adalah creator grup atau admin global
        $group = ChatGroup::find($groupId);

        // Penting: Anda mungkin ingin membuat role 'admin' di User model atau logic yang lebih canggih di sini
        // Untuk saat ini, asumsikan hanya creator_id grup atau user dengan role 'admin' global yang bisa posting
        if (!$group || ($group->creator_id !== $currentUser->id && $currentUser->role !== 'admin')) {
             return response()->json(['status' => 'error', 'message' => 'Anda tidak diizinkan memposting di grup ini.'], 403);
        }


        // Simpan pesan grup ke database
        $message = GroupMessage::create([
            'group_id' => $groupId,
            'sender_id' => $currentUser->id,
            'message_content' => $messageContent,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan berhasil dipublikasikan.',
            'sent_message' => [
                'id' => $message->id,
                'group_id' => $message->group_id,
                'sender_id' => $message->sender_id,
                'message_content' => $message->message_content,
                'created_at' => $message->created_at->format('H:i A, D M Y'), // Format waktu untuk tampilan
                'sender' => [ // Sertakan data pengirim
                    'full_name' => $currentUser->full_name,
                    'role' => $currentUser->role,
                    'profile_picture' => $currentUser->profile_picture,
                ]
            ]
        ], 200);
    }
}
