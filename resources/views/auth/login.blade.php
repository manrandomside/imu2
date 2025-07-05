@extends('layouts.app') {{-- Menggunakan layout dasar yang sudah kita buat --}}

@section('content')
{{-- REVISI KELAS PADA MAIN-CARD UNTUK POSITIONING --}}
{{-- Hapus flex items-center dari main-card agar positioning absolut tidak terganggu --}}
<div class="main-card w-full max-w-xl p-10 flex flex-col lg:absolute lg:top-1/2 lg:-translate-y-1/2 lg:right-10">
    {{-- START Flash Message Section --}}
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 w-full" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 w-full" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif
    {{-- END Flash Message Section --}}

    <h2 class="text-2xl font-bold mb-8 text-center">Make real connections with <br> students and alumni from <br> our campus!</h2>

    {{-- START: Tambahkan form tag di sini --}}
    <form method="POST" action="{{ route('login.store') }}" class="w-full">
        @csrf {{-- Penting untuk keamanan Laravel --}}
        <div class="w-full space-y-6">
            <div>
                <input type="email" id="email" name="email" placeholder="e-mail" class="input-field @error('email') border-red-500 @enderror" value="{{ old('email') }}">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <input type="password" id="password" name="password" placeholder="password" class="input-field @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="mt-8 w-full">
            <button type="submit" class="btn-primary w-full">Login Now</button>
            <p class="text-center text-sm mt-4">
                Don't have an account? <a href="{{ route('register.student') }}" class="text-link">Sign Up</a>
            </p>
        </div>
    </form> {{-- END: Penutup form tag --}}
</div>
@endsection