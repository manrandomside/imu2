@extends('layouts.app') {{-- Menggunakan layout dasar yang sudah kita buat --}}

@section('content')
<div class="relative w-full max-w-lg mx-auto h-[550px] overflow-hidden"> {{-- Container untuk kartu, tinggi tetap, tambahkan overflow-hidden --}}
    @forelse ($usersToDisplay as $index => $user)
        {{-- Kartu Profil Dinamis --}}
        {{-- z-index akan mengatur tumpukan kartu. Kartu pertama (index 0) paling depan.
             Kelas 'hidden' dan style 'transform'/'opacity' dikelola oleh JavaScript. --}}
        <div class="profile-card-swipe bg-white rounded-lg shadow-xl overflow-hidden absolute w-full h-full flex flex-col justify-between items-center text-gray-800 p-6 {{ $index > 0 ? 'hidden' : '' }}"
            style="z-index: {{ 10 - $index }}; {{ $index > 0 ? 'transform: scale(' . (1 - $index * 0.05) . ') translateY(' . ($index * 10) . 'px); opacity: ' . (1 - $index * 0.1) . ';' : '' }}"
            data-user-id="{{ $user->id }}"
            data-user-full-name="{{ $user->full_name }}"> {{-- Tambahkan data-user-full-name untuk popup match --}}

            <div class="w-full text-center mb-4">
                {{-- Gambar Profil Dinamis --}}
                <div class="w-40 h-40 rounded-full mx-auto bg-gray-200 flex items-center justify-center overflow-hidden mb-4 border-4 border-blue-400">
                    {{-- Menggunakan profile_picture dari user jika ada, jika tidak, pakai placeholder dengan inisial --}}
                    <img src="{{ $user->profile_picture ? asset($user->profile_picture) : 'https://via.placeholder.com/160/a8dadc/ffffff?text=' . strtoupper(substr($user->full_name, 0, 1)) }}" alt="Profile Picture" class="w-full h-full object-cover">
                </div>
                <h3 class="text-3xl font-bold mb-2">{{ $user->full_name }}</h3> {{-- Menampilkan full_name --}}
                <p class="text-lg text-gray-600">
                    {{ $user->gender }}
                    <span class="bg-blue-200 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full ml-2">{{ ucfirst($user->role) }}</span>
                </p> {{-- Menampilkan gender dan role --}}
                <p class="text-md text-gray-500">Qualification: {{ $user->prodi }}, {{ $user->fakultas }}</p> {{-- Menampilkan prodi, fakultas --}}
                <div class="flex flex-wrap justify-center gap-2 mt-3">
                    {{-- Menampilkan Interests --}}
                    @if ($user->interests && is_array($user->interests))
                        @foreach ($user->interests as $interest)
                            <span class="bg-blue-500 text-white text-xs font-semibold px-2.5 py-0.5 rounded-full"><i class="fas fa-tag"></i> {{ ucfirst(str_replace('_', ' ', $interest)) }}</span>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="text-center px-4 mb-4 flex-grow"> {{-- flex-grow agar deskripsi mengambil ruang --}}
                <p class="text-gray-700 leading-relaxed">
                    {{ $user->description ?? 'Deskripsi belum diisi.' }} {{-- Menampilkan deskripsi --}}
                </p>
            </div>

            {{-- Tombol Kontrol (X dan Checkmark) --}}
            <div class="flex justify-center gap-10 mt-auto w-full">
                {{-- Tombol 'X' untuk Tidak Tertarik --}}
                <button type="button" class="action-button reject-button bg-red-500 hover:bg-red-600 text-white text-3xl p-4 rounded-full shadow-lg transition-all duration-200 transform hover:scale-110" data-action="dislike"> {{-- Tambah data-action --}}
                    <i class="fas fa-times"></i>
                </button>
                {{-- Tombol 'Checkmark' untuk Tertarik --}}
                <button type="button" class="action-button accept-button bg-green-500 hover:bg-green-600 text-white text-3xl p-4 rounded-full shadow-lg transition-all duration-200 transform hover:scale-110" data-action="like"> {{-- Tambah data-action --}}
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    @empty
        {{-- Pesan jika tidak ada user untuk ditampilkan --}}
        <div class="main-card w-full h-full flex items-center justify-center text-center">
            <p class="text-white text-xl">Tidak ada pengguna lain yang memenuhi kriteria untuk ditampilkan saat ini.</p>
            <p class="text-gray-300 mt-2">Pastikan ada lebih banyak user terverifikasi di database dan profil mereka sudah lengkap.</p>
        </div>
    @endforelse
</div>

{{-- Modal Pop-up untuk Match --}}
<div id="match-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-xl text-center max-w-md w-full">
        <h2 class="text-3xl font-bold text-green-600 mb-4">ITS A MATCH!</h2>
        <p class="text-lg text-gray-700 mb-6">Anda dan <span id="matched-user-name" class="font-semibold"></span> saling menyukai!</p>
        <div class="flex justify-center space-x-4">
            <button id="start-chat-button" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">Mulai Chat</button>
            <button id="continue-swiping-button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold">Lanjutkan Mencari</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cardsContainer = document.querySelector('.relative.w-full.max-w-lg.mx-auto.h-\\[550px\\]');
        const cards = document.querySelectorAll('.profile-card-swipe');
        let currentCardIndex = 0;

        // Dapatkan token CSRF dari meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Elemen Modal
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
                currentCard.style.transition = 'none'; // Pastikan tidak ada transisi saat menampilkan
                currentCard.style.transform = 'none';
                currentCard.style.opacity = '1';
                currentCard.style.zIndex = '10';

                for (let i = currentCardIndex + 1; i < cards.length; i++) {
                    const nextCard = cards[i];
                    nextCard.classList.remove('hidden'); // Pastikan kartu belakang tidak hidden
                    const scaleFactor = 1 - (i - currentCardIndex) * 0.05;
                    const translateY = (i - currentCardIndex) * 10;
                    const opacityFactor = 1 - (i - currentCardIndex) * 0.1;

                    nextCard.style.transition = 'transform 0.3s ease-out, opacity 0.3s ease-out'; // Tambah transisi ke kartu belakang
                    nextCard.style.transform = `scale(${scaleFactor}) translateY(${translateY}px)`;
                    nextCard.style.opacity = `${opacityFactor}`;
                    nextCard.style.zIndex = `${10 - (i - currentCardIndex)}`; // Z-index menurun ke belakang
                }

                for (let i = currentCardIndex + 3; i < cards.length; i++) {
                    cards[i].classList.add('hidden');
                }

            } else {
                // Jika tidak ada kartu lagi
                // Bisa tampilkan pesan "Tidak ada user lagi" atau load lebih banyak
                const noMoreCardsMessage = document.createElement('div');
                noMoreCardsMessage.className = 'main-card w-full h-full absolute inset-0 flex items-center justify-center text-center';
                noMoreCardsMessage.innerHTML = '<p class="text-white text-xl">Tidak ada user lain untuk ditampilkan saat ini.</p><p class="text-gray-300 mt-2">Coba lagi nanti atau sesuaikan kriteria Anda.</p>';
                cardsContainer.appendChild(noMoreCardsMessage); // Tambahkan ke container kartu
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

        // Fungsi untuk mengirim interaksi ke backend
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

                    // Jika terjadi MATCH
                    if (data.matched) {
                        const matchedUserNameValue = currentCardElement.dataset.userFullName;
                        const matchedUserId = currentCardElement.dataset.userId; // Ambil ID user yang match
                        matchedUserName.textContent = matchedUserNameValue;
                        matchModal.classList.remove('hidden'); // Tampilkan modal

                        // Atur link Mulai Chat (ini akan kita implementasikan nanti)
                        // Untuk saat ini, bisa mengarah ke personal chat default
                        startChatButton.onclick = () => {
                            matchModal.classList.add('hidden');
                            window.location.href = `{{ url('/chat/personal') }}?with=${matchedUserId}`; // Arahkan ke chat spesifik
                        };

                        continueSwipingButton.onclick = () => {
                            matchModal.classList.add('hidden');
                            currentCardIndex++; // Lanjutkan ke kartu berikutnya setelah modal ditutup
                            showNextCard();
                        };
                    } else {
                        // Jika tidak match, lanjutkan ke kartu berikutnya seperti biasa
                        currentCardIndex++;
                        showNextCard();
                    }

                } else {
                    console.error('Interaksi gagal:', data.message || 'Terjadi kesalahan.');
                    alert('Gagal menyimpan interaksi: ' + (data.message || 'Terjadi kesalahan.'));
                    currentCardIndex++; // Tetap lanjutkan meskipun gagal, agar tidak stuck
                    showNextCard();
                }
            } catch (error) {
                console.error('Error saat mengirim interaksi:', error);
                alert('Terjadi error koneksi saat menyimpan interaksi.');
                currentCardIndex++; // Tetap lanjutkan meskipun error, agar tidak stuck
                showNextCard();
            }
        }

        // Inisialisasi tampilan kartu pertama
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

        // Event Listeners untuk tombol X dan Checkmark
        cards.forEach((card) => {
            const rejectButton = card.querySelector('.reject-button');
            const acceptButton = card.querySelector('.accept-button');
            const targetUserId = card.dataset.userId;
            const currentCardElement = card;

            if (rejectButton) {
                rejectButton.addEventListener('click', function() {
                    animateCardOut(currentCardElement, 'dislike', () => {
                        sendInteraction(targetUserId, 'dislike', currentCardElement);
                    });
                });
            }
            if (acceptButton) {
                // Tombol 'Accept' sekarang adalah button biasa, bukan lagi link <a>
                acceptButton.addEventListener('click', function() {
                    animateCardOut(currentCardElement, 'like', () => {
                        sendInteraction(targetUserId, 'like', currentCardElement);
                    });
                });
            }
        });
    });
</script>
