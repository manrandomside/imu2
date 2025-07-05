@extends('layouts.app') {{-- Menggunakan layout dasar yang sudah kita buat --}}

@section('content')
<div class="main-card profile-card w-full max-w-6xl p-8 flex flex-wrap lg:flex-nowrap gap-8 bg-orange-100 text-gray-800">
    <!-- Bagian Kiri: Profile Picture & Basic Info -->
    <div class="w-full lg:w-1/3 flex flex-col items-center p-6 bg-orange-200 rounded-lg shadow-inner text-center">
        {{-- Gambar Profil Dinamis --}}
        <div class="w-40 h-40 rounded-full mx-auto bg-gray-300 flex items-center justify-center overflow-hidden mb-4 border-4 border-blue-400">
            {{-- Menggunakan profile_picture dari user jika ada, jika tidak, pakai placeholder dengan inisial --}}
            <img src="{{ $user->profile_picture ? asset($user->profile_picture) : 'https://via.placeholder.com/160/a8dadc/ffffff?text=' . strtoupper(substr($user->full_name, 0, 1)) }}" alt="Profile Picture" class="w-full h-full object-cover">
        </div>
        <h2 class="text-3xl font-bold mb-1 text-orange-700">{{ $user->full_name }}</h2> {{-- Menampilkan full_name --}}
        <p class="text-lg text-gray-600">{{ $user->username }} <span class="bg-blue-200 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded-full ml-2">{{ ucfirst($user->role) }}</span></p> {{-- Menampilkan username dan role --}}
        <p class="text-md text-gray-500 mt-2">{{ $user->prodi ?? 'Prodi belum diisi' }}, {{ $user->fakultas ?? 'Fakultas belum diisi' }}</p> {{-- Menampilkan prodi, fakultas --}}
        <p class="text-md text-gray-500">{{ $user->gender ?? 'Gender belum diisi' }}</p> {{-- Menampilkan gender --}}
        <div class="mt-6 flex flex-col items-center">
            <a href="{{ route('profile.setup') }}" class="btn-primary bg-orange-600 hover:bg-orange-700 px-6 py-2 rounded-md">Edit Profile</a>
        </div>
    </div>

    <!-- Bagian Kanan: Deskripsi & Interests -->
    <div class="w-full lg:w-2/3 p-6">
        <h3 class="text-xl font-bold mb-4 text-orange-700">About Me</h3>
        <div class="bg-orange-50 border border-orange-300 rounded-lg p-4 mb-8 text-gray-700 leading-relaxed min-h-[100px] flex items-center justify-center text-center">
            <p>{{ $user->description ?? 'Belum ada deskripsi diri.' }}</p> {{-- Menampilkan deskripsi --}}
        </div>

        <h3 class="text-xl font-bold mb-4 text-orange-700">My Interests</h3>
        <div class="flex flex-wrap gap-3">
            @forelse ($user->interests as $interest)
                <span class="bg-blue-500 text-white px-4 py-2 rounded-full flex items-center space-x-2 text-sm font-semibold">
                    {{-- Menampilkan ikon berdasarkan minat, Anda bisa menyesuaikannya dengan array $interests di profile_setup.blade.php --}}
                    {{-- Untuk kesederhanaan, saya akan menggunakan ikon tag umum untuk demo ini --}}
                    <i class="fas fa-tag"></i> {{-- Menggunakan ikon tag umum --}}
                    <span>{{ ucfirst(str_replace('_', ' ', $interest)) }}</span>
                </span>
            @empty
                <p class="text-gray-500 text-sm">Belum ada minat yang dipilih.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
