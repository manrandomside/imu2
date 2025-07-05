@extends('layouts.app')

@section('content')
{{-- Container utama untuk feed berita --}}
{{-- Kelas 'main-content-area' dan style 'max-height' dihapus karena flexbox di app.blade.php yang akan mengaturnya --}}
<div class="w-full max-w-2xl mx-auto flex flex-col h-full bg-gray-50 rounded-lg shadow-xl text-gray-800">
    <div class="flex-grow overflow-y-auto p-4"> {{-- Area konten yang bisa di-scroll --}}

        {{-- Postingan Berita 1 --}}
        <div class="post-card bg-white p-6 rounded-lg shadow-md mb-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-200 mr-3 flex-shrink-0 overflow-hidden">
                    <img src="https://via.placeholder.com/48/4299e1/ffffff?text=U" alt="Profile" class="w-full h-full object-cover"> {{-- Placeholder untuk icon univ.udayana --}}
                </div>
                <div>
                    <p class="font-bold text-lg text-gray-900">univ.udayana</p>
                    <p class="text-sm text-gray-500">25 Maret 2025 - 5 April 2025</p> {{-- Waktu upload/periode --}}
                </div>
            </div>
            <img src="{{ asset('images/post_bem.png') }}" alt="News Image" class="w-full rounded-lg mb-4"> {{-- Placeholder untuk gambar postingan --}}
            <h4 class="font-semibold text-xl mb-2 text-gray-900">[PERPANJANGAN OPEN RECRUITMENT PANITIA INVENTION 2025]</h4>
            <p class="text-gray-700 leading-relaxed">
                Halo Civitas Informatika ✨<br><br>
                Great news for you! Karena antusiasme yang luar biasa, recruitment panitia INVENTION 2025 diperpanjang selama 7 hari. Buat kamu yang belum sempat daftar, this is your second chance. Kami memberikan kesempatan bagi mahasiswa/i Informatika untuk mendaftar diri pada:
                <br><br>
                Link Pendaftaran: <a href="#" class="text-blue-600 hover:underline">bit.ly/DaftarInvention2025</a><br>
                Deadline: 5 April 2025<br><br>
                Jangan sampai ketinggalan kesempatan emas ini untuk menjadi bagian dari INVENTION 2025! Ayo daftar sekarang dan rasakan pengalaman tak terlupakan bersama kami!
                <br><br>
                Narahubung: [nama]
                <br><br>
                #BeritaUnud #Invention2025 #OpenRecruitment #Panitia #UniversitasUdayana
            </p>
        </div>

        {{-- Postingan Berita 2 --}}
        <div class="post-card bg-white p-6 rounded-lg shadow-md mb-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-red-200 mr-3 flex-shrink-0 overflow-hidden">
                    <img src="https://via.placeholder.com/48/ef4444/ffffff?text=B" alt="Profile" class="w-full h-full object-cover"> {{-- Placeholder untuk icon bem.feb --}}
                </div>
                <div>
                    <p class="font-bold text-lg text-gray-900">bem.feb</p>
                    <p class="text-sm text-gray-500">20 Mei 2025</p> {{-- Contoh waktu upload --}}
                </div>
            </div>
            <img src="{{ asset('images/post_pkm.png') }}" alt="News Image" class="w-full rounded-lg mb-4">
            <h4 class="font-semibold text-xl mb-2 text-gray-900">OPEN RECRUITMENT TIM PKM 2025</h4>
            <p class="text-gray-700 leading-relaxed">
                Halo Civitas Akademika! 🚀<br><br>
                BEM FEB Udayana membuka kesempatan emas bagi mahasiswa/i yang ingin mengembangkan ide kreatifnya dalam Program Kreativitas Mahasiswa (PKM) 2025. Bergabunglah dengan tim kami dan raih prestasi di tingkat nasional!
                <br><br>
                Bidang PKM: Riset, Kewirausahaan, Pengabdian Masyarakat, Karsa Cipta.<br>
                Link Pendaftaran: <a href="#" class="text-blue-600 hover:underline">bit.ly/DaftarPKMBEMFEB2025</a><br>
                Deadline: 27 Mei 2025<br><br>
                Jangan lewatkan kesempatan untuk berinovasi dan berkarya. Ayo wujudkan ide-ide brilianmu bersama kami!
                <br><br>
                #PKM2025 #BEMFEB #InovasiMahasiswa #UdayanaBerprestasi
            </p>
        </div>

        {{-- Tambahkan lebih banyak postingan dummy agar scrollable --}}
        <div class="post-card bg-white p-6 rounded-lg shadow-md mb-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-purple-200 mr-3 flex-shrink-0 overflow-hidden">
                    <img src="https://via.placeholder.com/48/805ad5/ffffff?text=F" alt="Profile" class="w-full h-full object-cover">
                </div>
                <div>
                    <p class="font-bold text-lg text-gray-900">fakultas.teknik</p>
                    <p class="text-sm text-gray-500">10 Juni 2025</p>
                </div>
            </div>
            <img src="https://via.placeholder.com/600x300/a78bfa/ffffff?text=Seminar+Teknologi" alt="News Image" class="w-full rounded-lg mb-4">
            <h4 class="font-semibold text-xl mb-2 text-gray-900">SEMINAR NASIONAL TEKNOLOGI TERBARU</h4>
            <p class="text-gray-700 leading-relaxed">
                Fakultas Teknik Universitas Udayana dengan bangga mempersembahkan Seminar Nasional Teknologi Terbaru. Dapatkan wawasan mendalam dari para pakar industri dan akademisi.
                <br><br>
                Tanggal: 20 Juli 2025<br>
                Tempat: Auditorium Fakultas Teknik<br>
                Pendaftaran: <a href="#" class="text-blue-600 hover:underline">bit.ly/SeminarTeknikUnud2025</a>
            </p>
        </div>
        <div class="post-card bg-white p-6 rounded-lg shadow-md mb-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-200 mr-3 flex-shrink-0 overflow-hidden">
                    <img src="https://via.placeholder.com/48/4299e1/ffffff?text=U" alt="Profile" class="w-full h-full object-cover">
                </div>
                <div>
                    <p class="font-bold text-lg text-gray-900">univ.udayana</p>
                    <p class="text-sm text-gray-500">5 Juni 2025</p>
                </div>
            </div>
            <img src="https://via.placeholder.com/600x300/60a5fa/ffffff?text=Beasiswa" alt="News Image" class="w-full rounded-lg mb-4">
            <h4 class="font-semibold text-xl mb-2 text-gray-900">INFORMASI BEASISWA UNUD 2025/2026</h4>
            <p class="text-gray-700 leading-relaxed">
                Kesempatan beasiswa penuh dan parsial bagi mahasiswa/i berprestasi Universitas Udayana. Jangan lewatkan kesempatan ini!
                <br><br>
                Detail dan Persyaratan: <a href="#" class="text-blue-600 hover:underline">bit.ly/BeasiswaUnud2025</a><br>
                Deadline Pendaftaran: 30 Juni 2025
            </p>
        </div>

    </div> {{-- End of flex-grow overflow-y-auto --}}
</div> {{-- End of main content container --}}
@endsection