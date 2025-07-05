@extends('layouts.app') {{-- Menggunakan layout dasar yang sudah kita buat --}}

@section('content')
<div class="main-card w-full max-w-4xl flex">
    <div class="w-1/2 p-8 flex flex-col justify-center">
        <h2 class="text-2xl font-bold mb-6">Make real connections with <br> students and alumni from <br> our campus!</h2>
        {{-- INI ADALAH TAG FORM YANG DITAMBAHKAN/DIREVISI --}}
        <form method="POST" action="{{ route('register.store.student') }}">
            @csrf {{-- Penting untuk keamanan Laravel --}}
            <div class="space-y-4">
                <div>
                    <input type="text" id="full_name" name="full_name" placeholder="Full Name" class="input-field @error('full_name') border-red-500 @enderror" value="{{ old('full_name') }}">
                    @error('full_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <input type="text" id="username" name="username" placeholder="UserName" class="input-field @error('username') border-red-500 @enderror" value="{{ old('username') }}">
                    @error('username')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <input type="password" id="password" name="password" placeholder="Password" class="input-field @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <input type="password" id="confirm_password" name="password_confirmation" placeholder="Confirm Password" class="input-field">
                    {{-- Pesan error untuk password_confirmation akan muncul di bawah field password karena rule 'confirmed' --}}
                </div>
            </div>
            {{-- Bagian penutup form akan berada di luar div w-1/2 pertama --}}
    </div>
    <div class="w-1/2 p-8 flex flex-col justify-between">
        <div>
            <div class="flex space-x-4 mb-6">
                <button type="button" class="btn-primary px-6 py-2 rounded-full cursor-not-allowed opacity-70">Student</button> {{-- Aktif --}}
                <a href="{{ route('register.alumni') }}" class="btn-outline px-6 py-2 rounded-full">Alumni</a> {{-- Link ke registrasi alumni --}}
            </div>
            <div>
                <input type="email" id="email" name="email" placeholder="e-mail (@student.unud.ac.id)" class="input-field @error('email') border-red-500 @enderror" value="{{ old('email') }}"> {{-- PLACEHOLDER DIUBAH DI SINI --}}
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="mt-8">
            <button type="submit" class="btn-primary w-full">Register Now</button>
            <p class="text-center text-sm mt-4">
                Already have an account? <a href="{{ route('login') }}" class="text-link">Sign In</a> {{-- Hapus duplikasi teks --}}
            </p>
        </div>
    </div>
    </form> {{-- INI ADALAH PENUTUP TAG FORM YANG DITAMBAHKAN --}}
</div>
@endsection