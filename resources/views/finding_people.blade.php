@extends('layouts.app')

@section('content')

<div class="relative w-full max-w-lg mx-auto h-[600px] overflow-hidden">
    @forelse ($usersToDisplay as $index => $user)
        {{-- Kartu Profil Dinamis dengan Enhanced Categories --}}
        <div class="profile-card-swipe bg-white rounded-lg shadow-xl overflow-hidden absolute w-full h-full flex flex-col justify-between items-center text-gray-800 p-6 {{ $index > 0 ? 'hidden' : '' }}"
            style="z-index: {{ 10 - $index }}; {{ $index > 0 ? 'transform: scale(' . (1 - $index * 0.05) . ') translateY(' . ($index * 10) . 'px); opacity: ' . (1 - $index * 0.1) . ';' : '' }}"
            data-user-id="{{ $user->id }}"
            data-user-full-name="{{ $user->full_name }}">

            <div class="w-full text-center mb-4">
                {{-- Gambar Profil Dinamis --}}
                <div class="w-32 h-32 rounded-full mx-auto bg-gray-200 flex items-center justify-center overflow-hidden mb-4 border-4 border-blue-400">
                    <img src="{{ $user->profile_picture ? asset($user->profile_picture) : 'https://via.placeholder.com/128/a8dadc/ffffff?text=' . strtoupper(substr($user->full_name, 0, 1)) }}" alt="Profile Picture" class="w-full h-full object-cover">
                </div>
                
                <h3 class="text-2xl font-bold mb-2">{{ $user->full_name }}</h3>
                
                <p class="text-md text-gray-600 mb-1">
                    {{ $user->gender }}
                    <span class="bg-blue-200 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full ml-2">{{ ucfirst($user->role) }}</span>
                </p>
                
                <p class="text-sm text-gray-500 mb-3">{{ $user->prodi }}, {{ $user->fakultas }}</p>

                {{-- MATCH CATEGORIES - ORANGE TAGS --}}
                @if ($user->match_categories && is_array($user->match_categories) && count($user->match_categories) > 0)
                    <div class="match-categories mb-3">
                        <p class="text-xs font-semibold text-gray-700 mb-2">🔍 Looking For:</p>
                        <div class="flex flex-wrap justify-center gap-1 mb-2">
                            @foreach ($user->match_categories as $category)
                                <span class="bg-orange-500 text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                                    {{ ucfirst($category) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- MATCHING INDICATOR - GREEN --}}
                @php
                    $currentUserCategories = Auth::user()->match_categories ?? [];
                    $userCategories = $user->match_categories ?? [];
                    $matchingCategories = is_array($currentUserCategories) && is_array($userCategories) 
                                        ? array_intersect($currentUserCategories, $userCategories) 
                                        : [];
                @endphp
                
                @if(count($matchingCategories) > 0)
                    <div class="matching-indicator mb-3 p-2 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-700 text-xs font-medium">
                            🎯 <strong>Match:</strong> 
                            @foreach($matchingCategories as $match)
                                <span class="bg-green-500 text-white px-2 py-0.5 rounded text-xs ml-1">{{ ucfirst($match) }}</span>
                            @endforeach
                        </p>
                    </div>
                @endif

                {{-- INTERESTS - BLUE TAGS --}}
                @if ($user->interests && is_array($user->interests) && count($user->interests) > 0)
                    <div class="interests mb-3">
                        <p class="text-xs font-semibold text-gray-700 mb-2">💙 Interests:</p>
                        <div class="flex flex-wrap justify-center gap-1">
                            @foreach ($user->interests as $interest)
                                <span class="bg-blue-500 text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                                    {{ ucfirst(str_replace('_', ' ', $interest)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Description Section --}}
            <div class="text-center px-4 mb-4 flex-grow">
                <p class="text-gray-700 text-sm leading-relaxed">
                    {{ $user->description ?? 'Deskripsi belum diisi.' }}
                </p>
            </div>

            {{-- Tombol Kontrol (X dan Checkmark) --}}
            <div class="flex justify-center gap-8 mt-auto w-full">
                <button type="button" class="action-button reject-button bg-red-500 hover:bg-red-600 text-white text-2xl p-3 rounded-full shadow-lg transition-all duration-200 transform hover:scale-110" data-action="dislike">
                    <i class="fas fa-times"></i>
                </button>
                <button type="button" class="action-button accept-button bg-green-500 hover:bg-green-600 text-white text-2xl p-3 rounded-full shadow-lg transition-all duration-200 transform hover:scale-110" data-action="like">
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    @empty
        {{-- Pesan jika tidak ada user untuk ditampilkan --}}
        <div class="main-card w-full h-full flex items-center justify-center text-center">
            <div class="text-gray-600">
                <div class="text-6xl mb-4">🔍</div>
                <p class="text-xl font-semibold mb-2">Tidak ada user lain yang cocok</p>
                <p class="text-gray-500 text-sm mb-4">Coba lagi nanti atau sesuaikan kriteria pencarian Anda.</p>
                <a href="{{ route('find.people') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium">
                    🔄 Refresh & Find More
                </a>
            </div>
        </div>
    @endforelse
</div>

{{-- Modal Pop-up untuk Match --}}
<div id="match-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl text-center max-w-md w-full mx-4">
        <div class="text-6xl mb-4">🎉</div>
        <h2 class="text-3xl font-bold text-green-600 mb-4">IT'S A MATCH!</h2>
        <p class="text-lg text-gray-700 mb-6">Anda dan <span id="matched-user-name" class="font-semibold text-blue-600"></span> saling menyukai!</p>
        <div class="flex flex-col sm:flex-row justify-center space-y-2 sm:space-y-0 sm:space-x-4">
            <button id="start-chat-button" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                💬 Mulai Chat
            </button>
            <button id="continue-swiping-button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition-colors">
                ➡️ Lanjutkan Mencari
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cardsContainer = document.querySelector('.relative.w-full.max-w-lg.mx-auto.h-\\[600px\\]');
        const cards = document.querySelectorAll('.profile-card-swipe');
        let currentCardIndex = 0;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Modal elements
        const matchModal = document.getElementById('match-modal');
        const matchedUserName = document.getElementById('matched-user-name');
        const startChatButton = document.getElementById('start-chat-button');
        const continueSwipingButton = document.getElementById('continue-swiping-button');

        function showNextCard() {
            if (currentCardIndex < cards.length) {
                cards.forEach((card, idx) => {
                    if (idx < currentCardIndex) {
                        card.classList.add('hidden');
                    }
                });

                const currentCard = cards[currentCardIndex];
                currentCard.classList.remove('hidden');
                currentCard.style.transition = 'none';
                currentCard.style.transform = 'none';
                currentCard.style.opacity = '1';
                currentCard.style.zIndex = '10';

                for (let i = currentCardIndex + 1; i < cards.length; i++) {
                    const nextCard = cards[i];
                    nextCard.classList.remove('hidden');
                    const scaleFactor = 1 - (i - currentCardIndex) * 0.05;
                    const translateY = (i - currentCardIndex) * 10;
                    const opacityFactor = 1 - (i - currentCardIndex) * 0.1;

                    nextCard.style.transition = 'transform 0.3s ease-out, opacity 0.3s ease-out';
                    nextCard.style.transform = `scale(${scaleFactor}) translateY(${translateY}px)`;
                    nextCard.style.opacity = `${opacityFactor}`;
                    nextCard.style.zIndex = `${10 - (i - currentCardIndex)}`;
                }

                for (let i = currentCardIndex + 3; i < cards.length; i++) {
                    cards[i].classList.add('hidden');
                }
            } else {
                // No more cards
                const noMoreCardsMessage = document.createElement('div');
                noMoreCardsMessage.className = 'main-card w-full h-full absolute inset-0 flex items-center justify-center text-center p-8';
                noMoreCardsMessage.innerHTML = `
                    <div class="text-gray-600">
                        <div class="text-6xl mb-4">✨</div>
                        <p class="text-xl font-semibold mb-2">Hebat! Anda sudah melihat semua user</p>
                        <p class="text-gray-500 text-sm mb-4">Coba lagi nanti untuk melihat user baru</p>
                        <button onclick="window.location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium">
                            🔄 Refresh & Find More
                        </button>
                    </div>
                `;
                cardsContainer.appendChild(noMoreCardsMessage);
            }
        }

        function animateCardOut(card, direction, callback) {
            card.style.transition = 'transform 0.3s ease-out, opacity 0.3s ease-out';
            card.style.transform = `translateX(${direction === 'dislike' ? -500 : 500}px) rotate(${direction === 'dislike' ? -20 : 20}deg)`;
            card.style.opacity = '0';
            card.addEventListener('transitionend', function handler() {
                card.removeEventListener('transitionend', handler);
                if (callback) callback();
            });
        }

        // ✅ UPDATED: Send interaction to backend - WITH NOTIFICATION FEEDBACK
        async function sendInteraction(targetUserId, actionType, currentCardElement) {
            try {
                const response = await fetch('{{ route('user.interact') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        target_user_id: targetUserId,
                        action_type: actionType
                    })
                });

                const data = await response.json();
                if (response.ok) {
                    console.log('Interaksi berhasil:', data.message);

                    // ✅ Handle different response types based on backend response
                    if (data.matched) {
                        // MATCH! Show match modal
                        const matchedUserNameValue = currentCardElement.dataset.userFullName;
                        const matchedUserId = parseInt(currentCardElement.dataset.userId);
                        
                        matchedUserName.textContent = matchedUserNameValue;
                        matchModal.classList.remove('hidden');

                        startChatButton.onclick = () => {
                            matchModal.classList.add('hidden');
                            const chatUrl = '/chat/personal?with=' + matchedUserId;
                            console.log('Redirecting to:', chatUrl);
                            window.location.href = chatUrl;
                        };

                        continueSwipingButton.onclick = () => {
                            matchModal.classList.add('hidden');
                            currentCardIndex++;
                            showNextCard();
                        };
                    } 
                    else if (data.notification_sent && actionType === 'like') {
                        // ✅ LIKE SENT - Show feedback toast
                        showFeedbackToast(data.message, 'like');
                        currentCardIndex++;
                        showNextCard();
                    }
                    else if (actionType === 'dislike') {
                        // ✅ DISLIKE - Show brief feedback
                        showFeedbackToast(data.message || 'Pass. Mencari yang berikutnya...', 'dislike');
                        currentCardIndex++;
                        showNextCard();
                    }
                    else {
                        // ✅ Default case
                        showFeedbackToast(data.message || 'Interaksi disimpan.', 'default');
                        currentCardIndex++;
                        showNextCard();
                    }
                } else {
                    console.error('Interaksi gagal:', data.message || 'Terjadi kesalahan.');
                    showFeedbackToast('Gagal menyimpan interaksi: ' + (data.message || 'Terjadi kesalahan.'), 'error');
                    currentCardIndex++;
                    showNextCard();
                }
            } catch (error) {
                console.error('Error saat mengirim interaksi:', error);
                showFeedbackToast('Terjadi error koneksi saat menyimpan interaksi.', 'error');
                currentCardIndex++;
                showNextCard();
            }
        }

        // ✅ NEW: Show feedback toast function
        function showFeedbackToast(message, type) {
            // Remove existing toast if any
            const existingToast = document.getElementById('feedback-toast');
            if (existingToast) {
                existingToast.remove();
            }

            // Create toast element
            const toast = document.createElement('div');
            toast.id = 'feedback-toast';
            toast.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg text-white font-semibold z-50 transition-all duration-300 opacity-0';
            
            // Set background color and icon based on type
            switch(type) {
                case 'like':
                    toast.className += ' bg-pink-500';
                    toast.innerHTML = `<i class="fas fa-heart mr-2"></i>${message}`;
                    break;
                case 'dislike':
                    toast.className += ' bg-gray-500';
                    toast.innerHTML = `<i class="fas fa-times mr-2"></i>${message}`;
                    break;
                case 'error':
                    toast.className += ' bg-red-500';
                    toast.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i>${message}`;
                    break;
                default:
                    toast.className += ' bg-blue-500';
                    toast.innerHTML = `<i class="fas fa-info-circle mr-2"></i>${message}`;
            }

            // Add to DOM
            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(-50%) translateY(0)';
            }, 100);

            // Remove after 3 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(-20px)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, 3000);
        }

        // Initialize cards
        if (cards.length > 0) {
            cards.forEach((card, idx) => {
                if (idx > 0) {
                    card.classList.add('hidden');
                }
            });
            showNextCard();
        } else {
            showNextCard();
        }

        // Event listeners for buttons
        cards.forEach((card) => {
            const rejectButton = card.querySelector('.reject-button');
            const acceptButton = card.querySelector('.accept-button');
            const targetUserId = card.dataset.userId;

            if (rejectButton) {
                rejectButton.addEventListener('click', function() {
                    animateCardOut(card, 'dislike', () => {
                        sendInteraction(targetUserId, 'dislike', card);
                    });
                });
            }
            if (acceptButton) {
                acceptButton.addEventListener('click', function() {
                    animateCardOut(card, 'like', () => {
                        sendInteraction(targetUserId, 'like', card);
                    });
                });
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (currentCardIndex < cards.length) {
                const currentCard = cards[currentCardIndex];
                const targetUserId = currentCard.dataset.userId;
                
                if (e.key === 'ArrowLeft' || e.key === 'x' || e.key === 'X') {
                    e.preventDefault();
                    animateCardOut(currentCard, 'dislike', () => {
                        sendInteraction(targetUserId, 'dislike', currentCard);
                    });
                } else if (e.key === 'ArrowRight' || e.key === ' ') {
                    e.preventDefault();
                    animateCardOut(currentCard, 'like', () => {
                        sendInteraction(targetUserId, 'like', currentCard);
                    });
                }
            }
        });
    });
</script>
@endpush

@endsection