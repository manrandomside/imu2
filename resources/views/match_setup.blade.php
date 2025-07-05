@extends('layouts.app') {{-- Menggunakan layout dasar yang sudah kita buat --}}

@section('content')
{{-- Card utama untuk Set Up Matching --}}
<div class="main-card w-full max-w-2xl p-8 text-center bg-blue-100 text-gray-800">
    <h2 class="text-3xl font-bold mb-8 text-blue-700">What are you looking for?</h2>

    {{-- START: Tambahkan tag form di sini --}}
    <form method="POST" action="{{ route('profile.store_match_categories') }}"> {{-- Akan membuat rute ini --}}
        @csrf {{-- Penting untuk keamanan Laravel --}}
        <div class="grid grid-cols-2 gap-6" id="match-categories-container"> {{-- Tambahkan ID untuk JS --}}
            @php
                // Data kategori matching yang diambil dari SKPL Anda
                $matchCategories = [
                    'Friends' => 'fas fa-users',
                    'Jobs' => 'fas fa-briefcase',
                    'Committee' => 'fas fa-calendar-alt',
                    'PKM' => 'fas fa-lightbulb',
                    'KKN' => 'fas fa-map-marker-alt',
                    'Contest' => 'fas fa-trophy',
                ];
            @endphp

            @foreach ($matchCategories as $name => $iconClass)
                <button type="button" class="btn-match-category" data-category="{{ strtolower($name) }}">
                    <i class="{{ $iconClass }} text-2xl mb-2"></i>
                    <span>{{ $name }}</span>
                </button>
            @endforeach
        </div>

        {{-- Hidden input for match categories --}}
        <input type="hidden" name="match_categories" id="match-categories-hidden-input" value="{{ old('match_categories', Auth::user()->match_categories ? json_encode(Auth::user()->match_categories) : '[]') }}">
        @error('match_categories') {{-- Menampilkan error untuk match_categories --}}
            <p class="text-red-500 text-xs mt-3">{{ $message }}</p>
        @enderror

        <div class="mt-8 text-center">
            {{-- Tombol untuk melanjutkan --}}
            <button type="submit" class="btn-primary w-full max-w-xs bg-blue-600 hover:bg-blue-700">Continue</button>
        </div>
    </form> {{-- END: Penutup form tag --}}
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const categoriesContainer = document.getElementById('match-categories-container');
        const categoryButtons = categoriesContainer.querySelectorAll('button[data-category]');
        const categoriesHiddenInput = document.getElementById('match-categories-hidden-input');
        // Tidak ada batasan MAX_CATEGORIES di sini, pengguna bisa pilih banyak.
        // Jika Anda ingin batasan (misal: maks 3), tambahkan seperti di profile_setup.

        let selectedCategories = [];

        // Inisialisasi: Tandai kategori yang sudah tersimpan di database atau dari old() input
        let initialCategories = [];
        try {
            if (categoriesHiddenInput.value) {
                const parsedValue = JSON.parse(categoriesHiddenInput.value);
                if (Array.isArray(parsedValue)) {
                    initialCategories = parsedValue;
                }
            }
        } catch (e) {
            console.error("Error parsing initial match categories:", e);
        }

        selectedCategories = initialCategories;

        // Perbarui tampilan tombol berdasarkan selectedCategories saat ini
        categoryButtons.forEach(button => {
            if (selectedCategories.includes(button.dataset.category)) {
                button.classList.add('selected');
            }
        });
        updateButtonStates(); // Panggil untuk mengelola status disable/active

        // Listener untuk klik tombol kategori
        categoryButtons.forEach(button => {
            button.addEventListener('click', function () {
                const category = this.dataset.category;
                const isSelected = this.classList.contains('selected');

                if (isSelected) {
                    // Hapus dari daftar jika sudah dipilih
                    this.classList.remove('selected');
                    selectedCategories = selectedCategories.filter(item => item !== category);
                } else {
                    // Tambahkan ke daftar jika belum dipilih
                    // Jika ingin batasan, tambahkan if (selectedCategories.length < MAX_CATEGORIES) di sini
                    this.classList.add('selected');
                    selectedCategories.push(category);
                }
                // Update hidden input setiap kali kategori berubah
                categoriesHiddenInput.value = JSON.stringify(selectedCategories);
                updateButtonStates(); // Panggil untuk mengelola status disable/active
            });
        });

        // Fungsi untuk mengelola status disable tombol (jika ada batasan, atau untuk efek visual)
        function updateButtonStates() {
            // Jika ada batasan MAX_CATEGORIES, logikanya akan mirip dengan updateButtonStates di profile_setup.
            // Untuk saat ini, karena tidak ada batasan, fungsi ini mungkin tidak mengubah disabled state,
            // tetapi bisa digunakan untuk efek visual lain di masa depan.
        }
    });
</script>
@endpush
