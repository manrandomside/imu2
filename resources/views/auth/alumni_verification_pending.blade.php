@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-xl p-10 flex flex-col items-center text-center">
    <h2 class="text-3xl font-bold mb-4 text-white">Registrasi Berhasil!</h2>
    <p class="text-lg mb-6 text-gray-300">
        Akun alumni Anda berhasil didaftarkan.
    </p>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-6 py-4 rounded-lg relative w-full mb-8" role="alert">
        <strong class="font-bold">Verifikasi Sedang Diproses!</strong>
        <span class="block sm:inline">
            Mohon tunggu. Akun Anda sedang dalam proses verifikasi oleh administrator. Proses ini dapat memakan waktu hingga 1x24 jam. Kami akan memberitahu Anda melalui email setelah akun Anda aktif.
        </span>
    </div>
    <p class="text-md text-gray-400 mb-6">
        Anda dapat mencoba login kembali nanti untuk memeriksa status verifikasi.
    </p>
    <a href="{{ route('login') }}" class="btn-primary w-full max-w-xs">Kembali ke Halaman Login</a>
</div>
@endsection