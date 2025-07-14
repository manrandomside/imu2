{{-- resources/views/admin/alumni-approval/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="main-content-wrapper">
    <div class="max-w-6xl mx-auto">
        <!-- Header with Breadcrumb -->
        <div class="mb-8">
            <div class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                <a href="{{ url('/admin/alumni-approval') }}" class="hover:text-blue-600 transition-colors">
                    <i class="fas fa-users mr-1"></i>
                    Alumni Approval
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-gray-700 font-medium">Detail Alumni</span>
            </div>
            
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-user-graduate text-blue-500 mr-3"></i>
                        {{ $alumni->full_name }}
                    </h1>
                    <p class="text-gray-600">Review dan validasi data alumni</p>
                </div>
                <a href="{{ url('/admin/alumni-approval') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-400 text-green-700 p-4 mb-6 rounded-r-lg" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="ml-auto" onclick="this.parentElement.parentElement.style.display='none'">
                        <i class="fas fa-times text-green-500 hover:text-green-700"></i>
                    </button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-400 text-red-700 p-4 mb-6 rounded-r-lg" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="ml-auto" onclick="this.parentElement.parentElement.style.display='none'">
                        <i class="fas fa-times text-red-500 hover:text-red-700"></i>
                    </button>
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Alumni Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Card -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-8">
                        <div class="flex items-center space-x-6">
                            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-3xl font-bold text-white">
                                {{ strtoupper(substr($alumni->full_name, 0, 2)) }}
                            </div>
                            <div class="text-white">
                                <h2 class="text-2xl font-bold">{{ $alumni->full_name }}</h2>
                                <p class="text-blue-100 mt-1">@{{ $alumni->username }}</p>
                                <div class="flex items-center mt-3">
                                    <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                        <i class="fas fa-clock mr-1"></i>
                                        Menunggu Verifikasi
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            Informasi Personal
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Nama Lengkap</label>
                                    <p class="text-gray-900 font-medium">{{ $alumni->full_name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Username</label>
                                    <p class="text-gray-900">
                                        <span class="bg-gray-100 px-3 py-1 rounded-full text-sm">{{ $alumni->username }}</span>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Program Studi</label>
                                    <p class="text-gray-900">
                                        @if($alumni->prodi)
                                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">{{ $alumni->prodi }}</span>
                                        @else
                                            <span class="text-gray-400 italic">Tidak diisi</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Fakultas</label>
                                    <p class="text-gray-900">
                                        @if($alumni->fakultas)
                                            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">{{ $alumni->fakultas }}</span>
                                        @else
                                            <span class="text-gray-400 italic">Tidak diisi</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Gender</label>
                                    <p class="text-gray-900">
                                        @if($alumni->gender)
                                            <i class="fas fa-{{ $alumni->gender == 'male' ? 'mars text-blue-500' : 'venus text-pink-500' }} mr-2"></i>
                                            {{ $alumni->gender == 'male' ? 'Laki-laki' : 'Perempuan' }}
                                        @else
                                            <span class="text-gray-400 italic">Tidak diisi</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Tanggal Daftar</label>
                                    <p class="text-gray-900">
                                        <i class="fas fa-calendar text-green-500 mr-2"></i>
                                        {{ $alumni->created_at->format('d F Y, H:i') }} WIB
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if($alumni->description)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <label class="text-sm font-medium text-gray-500">Deskripsi</label>
                            <div class="mt-2 p-4 bg-gray-50 rounded-lg">
                                <p class="text-gray-700">{{ $alumni->description }}</p>
                            </div>
                        </div>
                        @endif

                        @if($alumni->interests && is_array($alumni->interests) && count($alumni->interests) > 0)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <label class="text-sm font-medium text-gray-500 mb-3 block">Minat & Hobi</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($alumni->interests as $interest)
                                    <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">
                                        <i class="fas fa-heart text-xs mr-1"></i>
                                        {{ $interest }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Contact & Verification Card -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-envelope text-blue-500 mr-2"></i>
                            Kontak & Verifikasi
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Email Status -->
                            <div class="p-4 rounded-lg border">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-envelope text-gray-500 mr-2"></i>
                                            Email
                                        </h4>
                                        @if($alumni->email)
                                            <p class="text-gray-700 mb-2">{{ $alumni->email }}</p>
                                            @if(str_contains($alumni->email, '@unud.ac.id') || str_contains($alumni->email, '@student.unud.ac.id'))
                                                <div class="flex items-center text-green-600">
                                                    <i class="fas fa-check-circle mr-2"></i>
                                                    <span class="font-medium">Email UNUD Terverifikasi</span>
                                                </div>
                                                <p class="text-sm text-green-600 mt-1">Alumni menggunakan email resmi UNUD yang valid</p>
                                            @else
                                                <div class="flex items-center text-yellow-600">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    <span class="font-medium">Email Non-UNUD</span>
                                                </div>
                                                <p class="text-sm text-yellow-600 mt-1">Email bukan dari domain UNUD resmi</p>
                                            @endif
                                        @else
                                            <div class="flex items-center text-gray-400">
                                                <i class="fas fa-times-circle mr-2"></i>
                                                <span>Tidak ada email</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        @if($alumni->email && (str_contains($alumni->email, '@unud.ac.id') || str_contains($alumni->email, '@student.unud.ac.id')))
                                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <i class="fas fa-shield-check mr-1"></i>
                                                Valid
                                            </span>
                                        @else
                                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <i class="fas fa-exclamation mr-1"></i>
                                                Perlu Review
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Document Status -->
                            <div class="p-4 rounded-lg border">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-file-alt text-gray-500 mr-2"></i>
                                            Dokumen Verifikasi
                                        </h4>
                                        @if($alumni->verification_doc_path)
                                            <div class="flex items-center text-green-600 mb-2">
                                                <i class="fas fa-file-check mr-2"></i>
                                                <span class="font-medium">Dokumen Tersedia</span>
                                            </div>
                                            <p class="text-sm text-green-600 mb-3">Alumni telah mengupload dokumen verifikasi</p>
                                            <a href="{{ url('/admin/alumni-approval/' . $alumni->id . '/download') }}" 
                                               class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm">
                                                <i class="fas fa-download mr-2"></i>
                                                Download Dokumen
                                            </a>
                                        @else
                                            <div class="flex items-center text-red-600">
                                                <i class="fas fa-file-times mr-2"></i>
                                                <span class="font-medium">Tidak Ada Dokumen</span>
                                            </div>
                                            <p class="text-sm text-red-600 mt-1">Alumni tidak mengupload dokumen verifikasi</p>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        @if($alumni->verification_doc_path)
                                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <i class="fas fa-check mr-1"></i>
                                                Ada
                                            </span>
                                        @else
                                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <i class="fas fa-times mr-1"></i>
                                                Kosong
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Actions & Status -->
            <div class="space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                        Status Overview
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-clock text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Status</span>
                            </div>
                            <span class="text-yellow-700 font-medium">Pending</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-envelope text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Email</span>
                            </div>
                            @if($alumni->email && (str_contains($alumni->email, '@unud.ac.id') || str_contains($alumni->email, '@student.unud.ac.id')))
                                <span class="text-green-700 font-medium">Valid</span>
                            @else
                                <span class="text-red-700 font-medium">Invalid</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-file text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Dokumen</span>
                            </div>
                            @if($alumni->verification_doc_path)
                                <span class="text-green-700 font-medium">Ada</span>
                            @else
                                <span class="text-red-700 font-medium">Kosong</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-cogs text-blue-500 mr-2"></i>
                        Aksi Admin
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Approve Button -->
                        <form method="POST" action="{{ url('/admin/alumni-approval/' . $alumni->id . '/approve') }}" class="w-full" id="approveForm">
                            @csrf
                            <button type="submit" 
                                    class="w-full bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-lg transition-colors duration-200 font-medium flex items-center justify-center"
                                    id="approveBtn">
                                <i class="fas fa-check-circle mr-2"></i>
                                Setujui Alumni
                            </button>
                        </form>

                        <!-- Reject Button -->
                        <form method="POST" action="{{ url('/admin/alumni-approval/' . $alumni->id . '/reject') }}" class="w-full" id="rejectForm">
                            @csrf
                            <button type="submit" 
                                    class="w-full bg-red-500 hover:bg-red-600 text-white py-3 px-4 rounded-lg transition-colors duration-200 font-medium flex items-center justify-center"
                                    id="rejectBtn">
                                <i class="fas fa-times-circle mr-2"></i>
                                Tolak & Hapus
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                <div class="text-sm text-blue-700">
                                    <p class="font-medium mb-1">Catatan Penting:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>Alumni yang disetujui akan dapat akses penuh ke sistem</li>
                                        <li>Alumni yang ditolak akan dihapus permanen</li>
                                        <li>Pastikan verifikasi email dan dokumen sebelum approval</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Info -->
                <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                    <div class="text-center">
                        <i class="fas fa-shield-alt text-4xl text-green-500 mb-3"></i>
                        <h4 class="text-green-800 font-semibold mb-2">Sistem Audit</h4>
                        <p class="text-sm text-green-700">
                            Semua tindakan tercatat dalam log audit untuk keamanan dan transparansi
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Alumni Detail Page Loaded');
    
    // Check CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.warn('CSRF token not found - this may cause form submission issues');
    }
    
    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('[role="alert"]');
        alerts.forEach(function(alert) {
            if (alert.style.display !== 'none') {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }
        });
    }, 5000);

    // Handle Approve Button
    const approveForm = document.getElementById('approveForm');
    const approveBtn = document.getElementById('approveBtn');
    
    if (approveForm && approveBtn) {
        approveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Approve button clicked');
            
            if (confirm('Apakah Anda yakin ingin menyetujui alumni {{ $alumni->full_name }}?\n\nAlumni akan mendapat akses penuh ke sistem.')) {
                // Add loading state
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
                this.disabled = true;
                this.classList.add('opacity-75');
                
                console.log('Submitting approve form to:', approveForm.action);
                
                // Submit form
                approveForm.submit();
                
                // Fallback timeout
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                    this.classList.remove('opacity-75');
                }, 10000);
            }
        });
    }

    // Handle Reject Button
    const rejectForm = document.getElementById('rejectForm');
    const rejectBtn = document.getElementById('rejectBtn');
    
    if (rejectForm && rejectBtn) {
        rejectBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Reject button clicked');
            
            if (confirm('PERINGATAN!\n\nApakah Anda yakin ingin menolak dan menghapus alumni {{ $alumni->full_name }}?\n\nTindakan ini akan:\n- Menghapus data alumni secara PERMANEN\n- Menghapus dokumen verifikasi\n- Tidak dapat dibatalkan\n\nKetik "HAPUS" untuk konfirmasi:')) {
                const confirmText = prompt('Ketik "HAPUS" untuk konfirmasi penghapusan:');
                
                if (confirmText === 'HAPUS') {
                    // Add loading state
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menghapus...';
                    this.disabled = true;
                    this.classList.add('opacity-75');
                    
                    console.log('Submitting reject form to:', rejectForm.action);
                    
                    // Submit form
                    rejectForm.submit();
                    
                    // Fallback timeout
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                        this.classList.remove('opacity-75');
                    }, 10000);
                } else {
                    alert('Konfirmasi tidak sesuai. Tindakan dibatalkan.');
                }
            }
        });
    }

    // Enhanced form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submitting:', this.action);
            
            // Check if form has CSRF token
            const csrfInput = this.querySelector('input[name="_token"]');
            if (!csrfInput) {
                console.error('CSRF token missing in form!');
                e.preventDefault();
                alert('Error: CSRF token missing. Please refresh the page and try again.');
                return false;
            }
            
            console.log('CSRF token found:', csrfInput.value.substring(0, 10) + '...');
        });
    });

    // Debug network requests
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        console.log('Fetch request:', args);
        return originalFetch.apply(this, args);
    };

    // Ensure no CSS is blocking clicks
    const bodyStyle = window.getComputedStyle(document.body);
    if (bodyStyle.pointerEvents === 'none') {
        console.warn('Fixing pointer events...');
        document.body.style.pointerEvents = 'auto';
    }

    // Check for form action URLs
    forms.forEach(function(form) {
        console.log('Form found:', form.action, form.method);
    });

    // Monitor button clicks
    document.querySelectorAll('button').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            console.log('Button clicked:', this.textContent.trim(), 'Type:', this.type);
        });
    });
});
</script>

<!-- Enhanced CSS -->
<style>
/* Ensure maximum clickability */
* {
    pointer-events: auto !important;
}

/* Button hover animations */
button:hover, a:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Loading states */
.loading {
    position: relative;
    pointer-events: none;
}

.loading::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 10;
}

/* Smooth transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Ensure no CSS conflicts */
.main-content-wrapper {
    position: relative !important;
    z-index: 1 !important;
    pointer-events: auto !important;
}

/* Enhanced accessibility */
button:focus, a:focus {
    outline: 2px solid #3B82F6;
    outline-offset: 2px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .grid-cols-1.lg\\:grid-cols-3 {
        grid-template-columns: 1fr;
    }
    
    .text-3xl {
        font-size: 1.875rem;
    }
    
    .px-6 {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>
@endsection