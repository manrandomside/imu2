@extends('layouts.app')

@section('content')
{{-- Container utama untuk seluruh halaman chat --}}
<div class="main-card w-full max-w-6xl h-[600px] flex bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">

    {{-- LEFT SIDE: Chat List / Contacts --}}
    <div class="flex flex-col w-full lg:w-1/3 bg-gray-50 p-4 overflow-y-auto border-r border-gray-200">
        <h4 class="text-lg font-bold text-gray-700 mb-4">Your Chats</h4>
        <div class="space-y-3">
            @forelse ($chatListUsers as $chatUser)
                <a href="{{ route('chat.personal', ['with' => $chatUser->id]) }}" 
                   class="chat-list-item flex items-center p-3 rounded-lg {{ $chatUser->is_active ? 'bg-blue-100' : 'hover:bg-gray-100' }} transition-colors relative">
                    
                    {{-- Profile Picture --}}
                    <div class="w-12 h-12 rounded-full bg-gray-300 mr-3 flex-shrink-0 overflow-hidden relative">
                        <img src="{{ $chatUser->profile_picture ?? 'https://via.placeholder.com/48/cccccc/ffffff?text=' . strtoupper(substr($chatUser->full_name ?? '', 0, 1)) }}" 
                             alt="Avatar" class="w-full h-full object-cover">
                        
                        {{-- Unread indicator --}}
                        @if(isset($chatUser->has_unread) && $chatUser->has_unread)
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-xs font-bold">•</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-800 truncate">{{ $chatUser->full_name }}</p>
                        <p class="text-sm {{ $chatUser->is_active ? 'text-blue-600' : 'text-gray-500' }} truncate">
                            {{ $chatUser->last_message }}
                        </p>
                        @if(isset($chatUser->last_message_time))
                            <p class="text-xs text-gray-400">{{ $chatUser->last_message_time }}</p>
                        @endif
                    </div>
                </a>
            @empty
                <div class="text-center py-8">
                    <div class="text-4xl mb-2">💬</div>
                    <p class="text-gray-500 text-sm">Tidak ada chat aktif.</p>
                    <a href="{{ route('find.people') }}" 
                       class="text-blue-500 text-sm hover:underline">
                        Cari orang untuk di-match!
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- RIGHT SIDE: Chat Messages Area --}}
    <div class="flex flex-col w-full lg:w-2/3">
        @if(!empty($selectedOtherUser))
            {{-- Chat Header --}}
            <div class="bg-gray-200 p-4 flex items-center border-b border-gray-300">
                <div class="w-10 h-10 rounded-full bg-gray-400 mr-3 flex-shrink-0 overflow-hidden">
                    <img src="{{ $selectedOtherUser->profile_picture ?? 'https://via.placeholder.com/40/cccccc/ffffff?text=' . strtoupper(substr($selectedOtherUser->full_name ?? '', 0, 1)) }}" 
                         alt="User Avatar" class="w-full h-full object-cover">
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">{{ $selectedOtherUser->full_name }}</h3>
                    <p class="text-sm text-gray-600">{{ $selectedOtherUser->prodi ?? '' }}</p>
                </div>
            </div>

            {{-- Chat Messages Display Area --}}
            <div id="messages-container" class="flex-grow p-4 overflow-y-auto space-y-4 bg-gray-100">
                @forelse ($messages as $message)
                    @if ($message->sender_id === $currentUser->id)
                        {{-- Pesan Keluar (Sender) --}}
                        <div class="flex justify-end">
                            <div class="bg-blue-500 text-white p-3 rounded-lg max-w-[70%] shadow">
                                <p class="break-words">{{ $message->message_content }}</p>
                                <span class="text-xs text-blue-100 block text-right mt-1">
                                    {{ $message->created_at->format('H:i A') }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Pesan Masuk (Receiver) --}}
                        <div class="flex justify-start">
                            <div class="bg-gray-300 text-gray-800 p-3 rounded-lg max-w-[70%] shadow">
                                <p class="break-words">{{ $message->message_content }}</p>
                                <span class="text-xs text-gray-500 block text-left mt-1">
                                    {{ $message->created_at->format('H:i A') }}
                                </span>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center py-8">
                        <div class="text-4xl mb-2">👋</div>
                        <p class="text-gray-500 text-lg font-medium">Say Hello!</p>
                        <p class="text-gray-400 text-sm">Mulai percakapan dengan {{ $selectedOtherUser->full_name }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Chat Input Area --}}
            <div class="bg-white p-4 border-t border-gray-300 flex items-center">
                <input type="text" 
                       id="message-input" 
                       placeholder="Ketik pesan..." 
                       class="input-field-chat flex-grow p-3 rounded-full bg-gray-200 border-gray-300 text-gray-800 placeholder-gray-500 mr-3 focus:outline-none focus:ring-2 focus:ring-blue-400"
                       maxlength="1000">
                
                <button id="send-message-button" 
                        class="bg-blue-500 text-white p-3 rounded-full flex items-center justify-center w-12 h-12 hover:bg-blue-600 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        @else
            {{-- No Chat Selected State --}}
            <div class="flex-1 flex items-center justify-center bg-gray-100">
                <div class="text-center">
                    <div class="text-6xl mb-4">💬</div>
                    <p class="text-xl font-semibold text-gray-700 mb-2">Pilih Chat</p>
                    <p class="text-gray-500">Pilih seseorang dari daftar chat untuk memulai percakapan</p>
                    @if(count($chatListUsers) === 0)
                        <div class="mt-4">
                            <a href="{{ route('find.people') }}" 
                               class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium">
                                🔍 Cari Orang Baru
                            </a>
                        </div>
                    @endif
                </div>
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

        // Scroll ke bawah saat halaman dimuat
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Data chat aktif
        const activeChatData = {
            receiverId: {{ $selectedOtherUser->id ?? 'null' }},
            matchId: {{ $activeMatch->id ?? 'null' }},
            receiverName: '{{ $selectedOtherUser->full_name ?? '' }}'
        };

        // Validasi data chat
        const isChatValid = activeChatData.receiverId && activeChatData.matchId;

        if (isChatValid && messageInput && sendMessageButton) {
            // Event listeners
            sendMessageButton.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Enable/disable send button based on input
            messageInput.addEventListener('input', function() {
                const hasContent = this.value.trim().length > 0;
                sendMessageButton.disabled = !hasContent;
                sendMessageButton.classList.toggle('bg-gray-400', !hasContent);
                sendMessageButton.classList.toggle('bg-blue-500', hasContent);
            });

            async function sendMessage() {
                const messageContent = messageInput.value.trim();
                
                if (messageContent === '') {
                    messageInput.focus();
                    return;
                }

                // Disable input sementara
                const originalButtonContent = sendMessageButton.innerHTML;
                sendMessageButton.disabled = true;
                sendMessageButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                messageInput.disabled = true;

                try {
                    const response = await fetch('{{ route('chat.send_message') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            receiver_id: activeChatData.receiverId,
                            match_id: activeChatData.matchId,
                            message_content: messageContent
                        })
                    });

                    const data = await response.json();
                    
                    if (response.ok) {
                        // Tambahkan pesan ke UI
                        appendMessageToUI(data.sent_message, true);
                        messageInput.value = '';
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        
                        // Show success feedback
                        showFeedback('Pesan terkirim', 'success');
                    } else {
                        console.error('Gagal mengirim pesan:', data.message);
                        showFeedback(data.message || 'Gagal mengirim pesan', 'error');
                    }
                } catch (error) {
                    console.error('Error saat mengirim pesan:', error);
                    showFeedback('Terjadi kesalahan koneksi', 'error');
                } finally {
                    // Re-enable input
                    sendMessageButton.disabled = false;
                    sendMessageButton.innerHTML = originalButtonContent;
                    messageInput.disabled = false;
                    messageInput.focus();
                }
            }

            function appendMessageToUI(message, isSender) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `flex ${isSender ? 'justify-end' : 'justify-start'}`;

                const messageBubble = document.createElement('div');
                messageBubble.className = `${isSender ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-800'} p-3 rounded-lg max-w-[70%] shadow`;
                
                messageBubble.innerHTML = `
                    <p class="break-words">${escapeHtml(message.message_content)}</p>
                    <span class="text-xs ${isSender ? 'text-blue-100' : 'text-gray-500'} block ${isSender ? 'text-right' : 'text-left'} mt-1">
                        ${message.timestamp}
                    </span>
                `;

                messageDiv.appendChild(messageBubble);
                messagesContainer.appendChild(messageDiv);
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function showFeedback(message, type) {
                // Simple feedback toast
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
                toast.textContent = message;
                
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }

            // Auto-scroll to bottom on new messages
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                });
            });

            observer.observe(messagesContainer, { childList: true });
        }

        // Initial button state
        if (sendMessageButton && messageInput) {
            sendMessageButton.disabled = messageInput.value.trim().length === 0;
        }
    });
</script>
@endpush

@endsection