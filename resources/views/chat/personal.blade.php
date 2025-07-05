@extends('layouts.app')

@section('content')
{{-- Container utama untuk seluruh halaman chat (termasuk sidebar) --}}
<div class="main-card w-full max-w-6xl h-[600px] flex bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">

    {{-- LEFT SIDE: Chat List / Contacts --}}
    <div class="flex flex-col w-full lg:w-1/3 bg-gray-50 p-4 overflow-y-auto border-r border-gray-200">
        <h4 class="text-lg font-bold text-gray-700 mb-4">Your Chats</h4>
        <div class="space-y-3">
            @forelse ($chatListUsers as $chatUser)
                {{-- Menandai chat yang aktif --}}
                {{-- Mengarahkan ke chat spesifik --}}
                <a href="{{ route('chat.personal', ['with' => $chatUser->id]) }}" class="chat-list-item flex items-center p-3 rounded-lg {{ $chatUser->is_active ? 'bg-blue-100' : 'hover:bg-gray-100' }} transition-colors">
                    <div class="w-10 h-10 rounded-full bg-gray-300 mr-3 flex-shrink-0 overflow-hidden">
                        {{-- Menggunakan profile_picture dari chatUser, atau placeholder --}}
                        <img src="{{ $chatUser->profile_picture ?? 'https://via.placeholder.com/40/cccccc/ffffff?text=' . strtoupper(substr($chatUser->full_name ?? '', 0, 1)) }}" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $chatUser->full_name }}</p>
                        <p class="text-sm {{ $chatUser->is_active ? 'text-blue-600' : 'text-gray-500' }}">
                            {{ $chatUser->last_message }}
                        </p>
                    </div>
                </a>
            @empty
                <p class="text-gray-500 text-sm text-center">Tidak ada chat aktif.</p>
            @endforelse
        </div>
    </div>

    {{-- RIGHT SIDE: Chat Messages Area --}}
    <div class="flex flex-col w-full lg:w-2/3">
        {{-- Chat Header --}}
        <div class="bg-gray-200 p-4 flex items-center border-b border-gray-300">
            <div class="w-10 h-10 rounded-full bg-gray-400 mr-3 flex-shrink-0 overflow-hidden">
                {{-- Menggunakan profile_picture dari selectedOtherUser, atau placeholder --}}
                <img src="{{ $selectedOtherUser->profile_picture ?? 'https://via.placeholder.com/40/cccccc/ffffff?text=' . strtoupper(substr($selectedOtherUser->full_name ?? '', 0, 1)) }}" alt="User Avatar" class="w-full h-full object-cover">
            </div>
            <h3 class="text-lg font-semibold text-gray-800">{{ $selectedOtherUser->full_name ?? 'Pilih Chat' }}</h3> {{-- Nama user chat --}}
        </div>

        {{-- Chat Messages Display Area --}}
        <div id="messages-container" class="flex-grow p-4 overflow-y-auto space-y-4 bg-gray-100">
            @forelse ($messages as $message)
                {{-- Tentukan apakah pesan masuk atau keluar --}}
                @if ($message->sender_id === $currentUser->id)
                    {{-- Pesan Keluar (Sender) --}}
                    <div class="flex justify-end">
                        <div class="bg-gray-300 text-gray-800 p-3 rounded-lg max-w-[70%] shadow">
                            <p>{{ $message->message_content }}</p>
                            <span class="text-xs text-gray-500 block text-right mt-1">{{ $message->created_at->format('H:i A') }}</span>
                        </div>
                    </div>
                @else
                    {{-- Pesan Masuk (Receiver) --}}
                    <div class="flex justify-start">
                        <div class="bg-blue-500 text-white p-3 rounded-lg max-w-[70%] shadow">
                            <p>{{ $message->message_content }}</p>
                            <span class="text-xs text-blue-100 block text-left mt-1">{{ $message->created_at->format('H:i A') }}</span>
                        </div>
                    </div>
                @endif
            @empty
                @if (empty($selectedOtherUser))
                    <p class="text-gray-500 text-center">Pilih seseorang dari daftar chat Anda untuk memulai percakapan.</p>
                @else
                    <p class="text-gray-500 text-center">Belum ada pesan dalam percakapan ini. Mulai obrolan baru!</p>
                @endif
            @endforelse
        </div>

        {{-- Chat Input Area --}}
        @if (!empty($selectedOtherUser))
            <div class="bg-white p-4 border-t border-gray-300 flex items-center">
                <input type="text" id="message-input" placeholder="Message.." class="input-field-chat flex-grow p-3 rounded-full bg-gray-200 border-gray-300 text-gray-800 placeholder-gray-500 mr-3 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <button id="send-message-button" class="bg-blue-500 text-white p-3 rounded-full flex items-center justify-center w-10 h-10 hover:bg-blue-600 transition-colors">
                    <i class="fas fa-paper-plane"></i> {{-- Icon send --}}
                </button>
            </div>
        @else
            <div class="bg-white p-4 border-t border-gray-300 flex items-center justify-center text-gray-500">
                Tidak ada chat yang dipilih.
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const messagesContainer = document.getElementById('messages-container');
        const messageInput = document.getElementById('message-input');
        const sendMessageButton = document.getElementById('send-message-button');

        // Scroll ke bawah setiap kali halaman dimuat atau pesan baru ditambahkan
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Dapatkan token CSRF dari meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Hanya aktifkan logika pengiriman pesan jika ada selectedOtherUser
        if (messageInput && sendMessageButton) {
            sendMessageButton.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Mencegah form submit default
                    sendMessage();
                }
            });

            async function sendMessage() {
                const messageContent = messageInput.value.trim();
                if (messageContent === '') {
                    return; // Jangan kirim pesan kosong
                }

                // Mengambil ID receiver dan match_id dari variabel Blade
                const receiverId = {{ $selectedOtherUser->id ?? 'null' }};
                const matchId = {{ $activeMatch->id ?? 'null' }};

                if (!receiverId || !matchId) {
                    console.error('Receiver ID or Match ID is missing. Cannot send message.');
                    alert('Tidak dapat mengirim pesan: Chat tidak aktif atau match tidak valid.');
                    return;
                }

                try {
                    const response = await fetch('{{ route('chat.send_message') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            receiver_id: receiverId,
                            match_id: matchId,
                            message_content: messageContent
                        })
                    });

                    const data = await response.json();
                    if (response.ok) {
                        console.log('Pesan berhasil dikirim:', data.message);
                        // Tambahkan pesan yang baru dikirim ke UI
                        appendMessageToUI(data.sent_message, true); // true menandakan pesan ini dikirim oleh user saat ini
                        messageInput.value = ''; // Bersihkan input
                        messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scroll ke bawah
                    } else {
                        console.error('Gagal mengirim pesan:', data.message || 'Terjadi kesalahan.');
                        alert('Gagal mengirim pesan: ' + (data.message || 'Terjadi kesalahan.'));
                    }
                } catch (error) {
                    console.error('Error saat mengirim pesan:', error);
                    alert('Terjadi error koneksi saat mengirim pesan.');
                }
            }

            // Fungsi untuk menambahkan pesan baru ke UI
            function appendMessageToUI(message, isSender) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `flex ${isSender ? 'justify-end' : 'justify-start'}`;

                const messageBubble = document.createElement('div');
                messageBubble.className = `${isSender ? 'bg-gray-300 text-gray-800' : 'bg-blue-500 text-white'} p-3 rounded-lg max-w-[70%] shadow`;
                // Perhatikan: message.timestamp di sini adalah string format dari backend
                messageBubble.innerHTML = `<p>${message.message_content}</p><span class="text-xs ${isSender ? 'text-gray-500 text-right' : 'text-blue-100 text-left'} block mt-1">${message.timestamp}</span>`;

                messageDiv.appendChild(messageBubble);
                messagesContainer.appendChild(messageDiv);
            }
        }
    });
</script>
@endpush
