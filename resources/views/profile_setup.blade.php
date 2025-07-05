@extends('layouts.app') {{-- Menggunakan layout dasar yang sudah kita buat --}}

@section('content')
<div class="main-card profile-card w-full max-w-6xl p-8 flex flex-wrap lg:flex-nowrap gap-8 bg-orange-100 text-gray-800">
    <!-- Bagian Kiri: Your Interests -->
    <div class="w-full lg:w-1/2 p-6 bg-orange-200 rounded-lg shadow-inner">
        <h3 class="text-xl font-bold mb-6 text-orange-700">Your Interests <span class="text-sm text-gray-600 font-normal">(Pilih maksimal 3)</span></h3> {{-- Tambahkan instruksi --}}
        <div class="flex flex-wrap gap-3" id="interests-container"> {{-- Tambahkan ID untuk JS --}}
            @php
                // Data interests yang diambil dari UI/UX Anda
                $interests = [
                    'Photography' => 'fas fa-camera', 'Shopping' => 'fas fa-shopping-bag', 'Karaoke' => 'fas fa-microphone',
                    'Yoga' => 'fas fa-leaf', 'Cooking' => 'fas fa-utensils', 'Tennis' => 'fas fa-tennis-ball',
                    'Run' => 'fas fa-running', 'Art' => 'fas fa-palette', 'Traveling' => 'fas fa-plane-departure',
                    'Extreme' => 'fas fa-mountain', 'Music' => 'fas fa-music', 'Drink' => 'fas fa-wine-glass',
                    'Video games' => 'fas fa-gamepad',
                ];
            @endphp

            @foreach ($interests as $name => $iconClass)
                <button type="button" class="btn-outline px-4 py-2 rounded-full flex items-center space-x-2 text-orange-700 border-orange-400 hover:bg-orange-700 hover:text-white transition-colors" data-interest="{{ strtolower(str_replace(' ', '_', $name)) }}"> {{-- Tambahkan data-interest, ganti spasi jadi underscore --}}
                    <i class="{{ $iconClass }}"></i>
                    <span>{{ $name }}</span>
                </button>
            @endforeach
        </div>
        @error('interests') {{-- Menampilkan error untuk interests --}}
            <p class="text-red-500 text-xs mt-3">{{ $message }}</p>
        @enderror
    </div>

    <!-- Bagian Kanan: Profile Details & Deskripsi -->
    <div class="w-full lg:w-1/2 p-6">
        <h3 class="text-xl font-bold mb-6 text-orange-700">Profile</h3>
        <form method="POST" action="{{ route('profile.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    {{-- Menggunakan Auth::user()->username untuk mengisi nilai --}}
                    <input type="text" id="username" name="username" placeholder="Username" class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 cursor-not-allowed" value="{{ Auth::user()->username ?? '' }}" readonly>
                </div>
                <div>
                    {{-- Menggunakan old() dan Auth::user()->full_name untuk mengisi nilai --}}
                    <input type="text" id="full_name" name="full_name" placeholder="Full Name" class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('full_name') border-red-500 @enderror" value="{{ old('full_name', Auth::user()->full_name) }}">
                    @error('full_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    {{-- Menggunakan old() dan Auth::user()->prodi untuk mengisi nilai --}}
                    <input type="text" id="prodi" name="prodi" placeholder="Prodi" class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('prodi') border-red-500 @enderror" value="{{ old('prodi', Auth::user()->prodi) }}">
                    @error('prodi')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    {{-- Menggunakan old() dan Auth::user()->fakultas untuk mengisi nilai --}}
                    <input type="text" id="fakultas" name="fakultas" placeholder="Fakultas" class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('fakultas') border-red-500 @enderror" value="{{ old('fakultas', Auth::user()->fakultas) }}">
                    @error('fakultas')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <select id="gender" name="gender" class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 @error('gender') border-red-500 @enderror">
                        <option value="">Select Gender</option>
                        <option value="Laki-laki" {{ old('gender', Auth::user()->gender) == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender', Auth::user()->gender) == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    @error('gender')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <h3 class="text-xl font-bold mb-4 text-orange-700">Deskripsi</h3>
            <div>
                <textarea id="description" name="description" placeholder="" class="input-field-orange w-full h-32 bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 resize-none @error('description') border-red-500 @enderror">{{ old('description', Auth::user()->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <input type="hidden" name="interests" id="interests-hidden-input" value="{{ old('interests', Auth::user()->interests ? json_encode(Auth::user()->interests) : '[]') }}">

            <div class="mt-8 text-center">
                <button type="submit" class="btn-primary w-full max-w-xs bg-orange-600 hover:bg-orange-700">Save Profile</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const interestsContainer = document.getElementById('interests-container');
        const interestButtons = interestsContainer.querySelectorAll('button[data-interest]');
        const interestsHiddenInput = document.getElementById('interests-hidden-input');
        const MAX_INTERESTS = 3; // Batasan maksimal minat

        let selectedInterests = [];

        // Inisialisasi: Tandai minat yang sudah tersimpan di database atau dari old() input
        let initialInterests = [];
        try {
            if (interestsHiddenInput.value) {
                const parsedValue = JSON.parse(interestsHiddenInput.value);
                if (Array.isArray(parsedValue)) {
                    initialInterests = parsedValue;
                }
            }
        } catch (e) {
            console.error("Error parsing initial interests:", e);
        }

        selectedInterests = initialInterests;

        // Perbarui tampilan tombol berdasarkan selectedInterests saat ini
        interestButtons.forEach(button => {
            if (selectedInterests.includes(button.dataset.interest)) {
                button.classList.add('selected');
            }
        });
        updateButtonStates(); // Panggil untuk pertama kali untuk mengelola status disable

        // Listener untuk klik tombol minat
        interestButtons.forEach(button => {
            button.addEventListener('click', function () {
                const interest = this.dataset.interest;
                const isSelected = this.classList.contains('selected');

                if (isSelected) {
                    // Jika sudah dipilih, hapus dari daftar
                    this.classList.remove('selected');
                    selectedInterests = selectedInterests.filter(item => item !== interest);
                } else {
                    // Jika belum dipilih dan kuota masih ada, tambahkan ke daftar
                    if (selectedInterests.length < MAX_INTERESTS) {
                        this.classList.add('selected');
                        selectedInterests.push(interest);
                    } else {
                        // Beri tahu pengguna bahwa batas sudah tercapai
                        alert(`Anda hanya dapat memilih maksimal ${MAX_INTERESTS} minat.`);
                    }
                }
                // Update hidden input setiap kali minat berubah
                interestsHiddenInput.value = JSON.stringify(selectedInterests);
                updateButtonStates(); // Panggil untuk mengelola status disable
            });
        });

        // Fungsi untuk mengelola status disable tombol ketika batas tercapai
        function updateButtonStates() {
            if (selectedInterests.length >= MAX_INTERESTS) {
                interestButtons.forEach(button => {
                    if (!button.classList.contains('selected')) {
                        button.disabled = true; // Disable tombol yang tidak terpilih
                        button.classList.add('opacity-50', 'cursor-not-allowed'); // Tambah efek visual disable
                    }
                });
            } else {
                interestButtons.forEach(button => {
                    button.disabled = false; // Aktifkan semua tombol jika belum batas
                    button.classList.remove('opacity-50', 'cursor-not-allowed'); // Hapus efek visual disable
                });
            }
        }
    });
</script>
@endpush
@endsection
