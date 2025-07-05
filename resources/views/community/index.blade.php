@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-4xl h-[600px] flex flex-col bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">

    {{-- TOP SECTION: Horizontal Scrollable Community List --}}
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex space-x-4 overflow-x-auto pb-2 scrollbar-hide">
            @forelse ($communities as $community)
                {{-- Mengarahkan ke grup spesifik dan menandai yang aktif --}}
                <a href="{{ route('community', ['group' => $community->id]) }}" class="community-circle flex-shrink-0 text-center {{ ($selectedGroup && $selectedGroup->id === $community->id) ? 'border-2 border-blue-600 p-1 rounded-full' : '' }}">
                    <img src="{{ $community->icon ?? 'https://via.placeholder.com/60/cccccc/ffffff?text=' . strtoupper(substr($community->name, 0, 1)) }}" alt="Community Icon" class="w-16 h-16 rounded-full mx-auto mb-1 {{ ($selectedGroup && $selectedGroup->id === $community->id) ? '' : 'border-2 border-transparent' }}">
                    <p class="text-xs font-semibold {{ ($selectedGroup && $selectedGroup->id === $community->id) ? 'text-blue-800' : 'text-gray-700' }}">{{ $community->name }}</p>
                </a>
            @empty
                <p class="text-gray-500 text-sm text-center">Tidak ada komunitas yang tersedia.</p>
            @endforelse
        </div>
    </div>

    {{-- MAIN SECTION: Community Chat/Post Feed (Vertical Scrollable) --}}
    <div class="flex flex-col flex-grow p-4 overflow-y-auto bg-gray-100">
        <h4 class="font-bold text-xl text-gray-900 mb-4">Posted in {{ $selectedGroup->name ?? 'Community' }}</h4>

        @forelse ($groupMessages as $message)
            {{-- Postingan dari Community --}}
            <div class="community-post-card bg-white p-4 rounded-lg shadow-md mb-4">
                <div class="flex items-center mb-2">
                    <div class="w-8 h-8 rounded-full bg-gray-300 mr-2 flex-shrink-0 overflow-hidden">
                        {{-- Menggunakan profile_picture pengirim pesan, atau placeholder --}}
                        <img src="{{ $message->sender->profile_picture ?? 'https://via.placeholder.com/32/cccccc/ffffff?text=' . strtoupper(substr($message->sender->full_name ?? '', 0, 1)) }}" alt="User Avatar" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $message->sender->full_name }} <span class="bg-blue-200 text-blue-800 text-xs font-semibold px-1.5 py-0.5 rounded-full ml-1">{{ ucfirst($message->sender->role) }}</span></p>
                        <p class="text-xs text-gray-500">{{ $message->created_at->format('H:i A, D M Y') }}</p> {{-- Menampilkan waktu kirim --}}
                    </div>
                    {{-- Link view community (placeholder) --}}
                    <a href="#" class="ml-auto text-sm text-blue-600 hover:underline">view community</a>
                </div>
                <p class="text-gray-700 mb-2">{!! $message->message_content !!}</p>
                <div class="flex items-center text-gray-500 text-sm">
                    {{-- Like/Comment/Share placeholder --}}
                    <span class="mr-4">
                        <i class="fas fa-heart mr-1"></i> 0
                    </span>
                    <span class="mr-4">
                        <i class="fas fa-comment mr-1"></i> 0
                    </span>
                    <span class="ml-auto">
                        <i class="fas fa-share-alt mr-1"></i> Share
                    </span>
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center">Belum ada postingan di komunitas ini.</p>
        @endforelse

        {{-- Area untuk "write your post here" --}}
        {{-- Hanya tampilkan input jika user adalah pembuat/admin grup atau admin global --}}
        {{-- Logika ini hanya simulasi, di backend perlu penanganan role yang lebih kuat --}}
        @if ($selectedGroup && ($currentUser->id === $selectedGroup->creator_id || $currentUser->role === 'admin'))
        <div class="write-post-area bg-white p-4 rounded-lg shadow-md mt-6">
            <textarea id="group-message-input" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none" rows="3" placeholder="write your post here"></textarea>
            <div class="flex justify-between items-center mt-3">
                <label class="text-gray-500 cursor-pointer">
                    <i class="fas fa-plus-circle mr-1"></i> Add your post in
                    <select id="community-select" class="ml-1 p-1 border rounded-md bg-gray-50 text-gray-700">
                        @forelse ($communities as $communityOption)
                            {{-- Hanya tampilkan grup yang dibuat oleh user ini atau jika user adalah admin --}}
                            @if ($communityOption->creator_id === $currentUser->id || $currentUser->role === 'admin')
                                <option value="{{ $communityOption->id }}" {{ ($selectedGroup && $selectedGroup->id === $communityOption->id) ? 'selected' : '' }}>{{ $communityOption->name }}</option>
                            @endif
                        @empty
                            <option value="">Tidak ada komunitas untuk diposting</option>
                        @endforelse
                    </select>
                </label>
                <button id="publish-group-post-button" class="btn-primary bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Publish Post</button>
            </div>
        </div>
        @else
            <p class="text-gray-500 text-center mt-6">Anda hanya dapat melihat postingan di komunitas ini.</p>
        @endif

    </div> {{-- End of Community Chat/Post Feed --}}

</div> {{-- End of main-card container --}}

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const groupMessageInput = document.getElementById('group-message-input');
        const publishGroupPostButton = document.getElementById('publish-group-post-button');
        const communitySelect = document.getElementById('community-select');
        const messagesContainer = document.querySelector('.flex-grow.p-4.overflow-y-auto'); // Container pesan grup

        // Scroll ke bawah saat halaman dimuat
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Pastikan elemen input ada sebelum menambahkan event listener
        if (groupMessageInput && publishGroupPostButton && communitySelect) {
            publishGroupPostButton.addEventListener('click', sendGroupMessage);
            groupMessageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) { // Kirim saat Enter, tapi tidak saat Shift+Enter
                    e.preventDefault();
                    sendGroupMessage();
                }
            });

            async function sendGroupMessage() {
                const messageContent = groupMessageInput.value.trim();
                const groupId = communitySelect.value;

                if (messageContent === '' || !groupId) {
                    alert('Pesan atau komunitas tidak boleh kosong.');
                    return;
                }

                try {
                    const response = await fetch('{{ route('community.send_message') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            group_id: groupId,
                            message_content: messageContent
                        })
                    });

                    const data = await response.json();
                    if (response.ok) {
                        console.log('Pesan grup berhasil dikirim:', data.message);
                        // Tambahkan pesan yang baru dikirim ke UI
                        appendGroupMessageToUI(data.sent_message);
                        groupMessageInput.value = ''; // Bersihkan input
                        messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scroll ke bawah
                    } else {
                        console.error('Gagal mengirim pesan grup:', data.message || 'Terjadi kesalahan.');
                        alert('Gagal mengirim pesan grup: ' + (data.message || 'Terjadi kesalahan.'));
                    }
                } catch (error) {
                    console.error('Error saat mengirim pesan grup:', error);
                    alert('Terjadi error koneksi saat mengirim pesan grup.');
                }
            }

            function appendGroupMessageToUI(message) {
                const postCard = document.createElement('div');
                postCard.className = 'community-post-card bg-white p-4 rounded-lg shadow-md mb-4';

                const senderHtml = `
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 rounded-full bg-gray-300 mr-2 flex-shrink-0 overflow-hidden">
                            <img src="{{ Auth::user()->profile_picture ?? 'https://via.placeholder.com/32/cccccc/ffffff?text=' . strtoupper(substr(Auth::user()->full_name ?? '', 0, 1)) }}" alt="User Avatar" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">{{ Auth::user()->full_name ?? 'Pengguna' }} <span class="bg-blue-200 text-blue-800 text-xs font-semibold px-1.5 py-0.5 rounded-full ml-1">{{ ucfirst(Auth::user()->role ?? 'member') }}</span></p>
                            <p class="text-xs text-gray-500">${message.created_at}</p> {{-- Menggunakan created_at dari respons backend --}}
                        </div>
                        <a href="#" class="ml-auto text-sm text-blue-600 hover:underline">view community</a>
                    </div>
                    <p class="text-gray-700 mb-2">${message.message_content}</p>
                    <div class="flex items-center text-gray-500 text-sm">
                        <span class="mr-4"><i class="fas fa-heart mr-1"></i> 0</span>
                        <span class="mr-4"><i class="fas fa-comment mr-1"></i> 0</span>
                        <span class="ml-auto"><i class="fas fa-share-alt mr-1"></i> Share</span>
                    </div>
                `;
                messagesContainer.appendChild(postCard);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
    });
</script>
@endpush
