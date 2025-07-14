{{-- resources/views/admin/alumni-approval/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="main-content-wrapper">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-user-graduate text-blue-500 mr-3"></i>
                    Alumni Approval Management
                </h1>
                <p class="text-gray-600">Kelola persetujuan pendaftaran alumni</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-full font-semibold">
                    <i class="fas fa-clock mr-2"></i>
                    {{ $pendingAlumni->count() }} Menunggu
                </span>
                <button onclick="refreshPage()" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
                <button onclick="clearNotificationCache()" 
                        class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-colors duration-200"
                        title="Clear navbar notification cache">
                    <i class="fas fa-bell-slash mr-2"></i>
                    Clear Notif
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="ml-auto" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times text-green-500 hover:text-green-700"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="ml-auto" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times text-red-500 hover:text-red-700"></i>
                </button>
            </div>
        @endif

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            @if($pendingAlumni->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="mb-6">
                        <i class="fas fa-user-check text-8xl text-gray-300"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-700 mb-2">Tidak Ada Alumni Menunggu</h3>
                    <p class="text-gray-500 mb-8">Semua permohonan alumni telah diproses</p>
                    <a href="{{ url('/home') }}" 
                       class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i>
                        Kembali ke Dashboard
                    </a>
                </div>
            @else
                <!-- Table Header -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-list mr-2"></i>
                            Daftar Alumni Pending
                        </h3>
                        <span class="text-sm text-gray-600">
                            Total: {{ $pendingAlumni->count() }} alumni
                        </span>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">#</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Informasi Alumni</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Kontak</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Program Studi</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Tanggal Daftar</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($pendingAlumni as $index => $alumni)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-blue-600">{{ $index + 1 }}</span>
                                    </td>
                                    
                                    <!-- Alumni Info -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold mr-4">
                                                {{ strtoupper(substr($alumni->full_name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">{{ $alumni->full_name }}</div>
                                                <div class="text-sm text-gray-500">
                                                    <i class="fas fa-user mr-1"></i>
                                                    {{ $alumni->username }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Contact Info -->
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm text-gray-900 mb-1">
                                                <i class="fas fa-envelope mr-1"></i>
                                                {{ $alumni->email ?: 'Tidak diisi' }}
                                            </div>
                                            @if($alumni->email && (str_contains($alumni->email, '@unud.ac.id') || str_contains($alumni->email, '@student.unud.ac.id')))
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    Email Valid
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Perlu Verifikasi
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    
                                    <!-- Program Studi -->
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            {{ $alumni->prodi ?: 'Tidak diisi' }}
                                        </span>
                                    </td>
                                    
                                    <!-- Status -->
                                    <td class="px-6 py-4">
                                        <div class="space-y-1">
                                            @if($alumni->verification_doc_path)
                                                <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-file-pdf mr-1"></i>
                                                    Dokumen Ada
                                                </div>
                                            @else
                                                <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <i class="fas fa-file-times mr-1"></i>
                                                    Tanpa Dokumen
                                                </div>
                                            @endif
                                            
                                            <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 block">
                                                <i class="fas fa-clock mr-1"></i>
                                                Menunggu Review
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Registration Date -->
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $alumni->created_at->format('d M Y') }}</div>
                                            <div class="text-sm text-gray-500">{{ $alumni->created_at->format('H:i') }}</div>
                                        </div>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center space-x-2">
                                            <!-- Detail Button -->
                                            <a href="{{ url('/admin/alumni-approval/' . $alumni->id) }}" 
                                               class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg transition-colors duration-200"
                                               title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <!-- Download Document (if exists) -->
                                            @if($alumni->verification_doc_path)
                                                <a href="{{ url('/admin/alumni-approval/' . $alumni->id . '/download') }}" 
                                                   class="bg-gray-500 hover:bg-gray-600 text-white p-2 rounded-lg transition-colors duration-200"
                                                   title="Download Dokumen">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endif
                                            
                                            <!-- Quick Approve -->
                                            <form method="POST" action="{{ url('/admin/alumni-approval/' . $alumni->id . '/approve') }}" 
                                                  class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-lg transition-colors duration-200"
                                                        title="Setujui Alumni"
                                                        onclick="return confirm('Apakah Anda yakin ingin menyetujui alumni {{ $alumni->full_name }}?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- Quick Reject -->
                                            <form method="POST" action="{{ url('/admin/alumni-approval/' . $alumni->id . '/reject') }}" 
                                                  class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg transition-colors duration-200"
                                                        title="Tolak Alumni"
                                                        onclick="return confirm('Apakah Anda yakin ingin menolak dan menghapus alumni {{ $alumni->full_name }}? Tindakan ini tidak dapat dibatalkan.')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-between items-center text-sm text-gray-600">
                        <span>
                            Menampilkan {{ $pendingAlumni->count() }} dari {{ $pendingAlumni->count() }} alumni
                        </span>
                        <span>
                            <i class="fas fa-info-circle mr-1"></i>
                            Klik tombol untuk melakukan aksi pada setiap alumni
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Help Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
            <div class="lg:col-span-2">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h4 class="text-blue-800 font-semibold mb-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        Panduan Approval Alumni
                    </h4>
                    <ul class="text-sm text-blue-700 space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-500 mr-2 mt-1"></i>
                            Periksa email alumni menggunakan domain @unud.ac.id atau @student.unud.ac.id
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-500 mr-2 mt-1"></i>
                            Download dan verifikasi dokumen pendukung jika tersedia
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-500 mr-2 mt-1"></i>
                            Gunakan tombol "Detail" untuk melihat informasi lengkap sebelum approve/reject
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-1"></i>
                            Alumni yang ditolak akan dihapus permanen dari sistem
                        </li>
                    </ul>
                </div>
            </div>
            <div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                    <i class="fas fa-shield-alt text-4xl text-green-500 mb-3"></i>
                    <h4 class="text-green-800 font-semibold mb-2">Sistem Aman</h4>
                    <p class="text-sm text-green-700">
                        Semua tindakan tercatat dan dapat diaudit
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-dismiss alerts after 5 seconds -->
<script>
document.addEventListener('DOMContentLoaded', function() {
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
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }
        });
    }, 5000);

    // Add loading state to form buttons
    document.querySelectorAll('form button[type="submit"]').forEach(function(button) {
        button.addEventListener('click', function() {
            console.log('Button clicked:', this);
            if (this.form.checkValidity()) {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;
                
                // Fallback to re-enable button
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }, 5000);
            }
        });
    });

    // Debug: Check all links
    document.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            console.log('Link clicked:', this.href);
        });
    });

    // Ensure no CSS is blocking clicks
    const bodyStyle = window.getComputedStyle(document.body);
    if (bodyStyle.pointerEvents === 'none') {
        console.warn('Body has pointer-events: none - fixing...');
        document.body.style.pointerEvents = 'auto';
    }

    // Check for any overlays
    const overlays = document.querySelectorAll('[style*="position: fixed"], [style*="position: absolute"]');
    overlays.forEach(function(overlay) {
        const zIndex = window.getComputedStyle(overlay).zIndex;
        if (parseInt(zIndex) > 1000) {
            console.warn('High z-index overlay detected:', overlay, 'z-index:', zIndex);
        }
    });
});

// ✅ REFRESH PAGE FUNCTION
function refreshPage() {
    // Show loading state
    const refreshBtn = event.target;
    const originalHtml = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
    refreshBtn.disabled = true;
    
    // Reload page
    setTimeout(() => {
        location.reload();
    }, 500);
}

// ✅ CLEAR NOTIFICATION CACHE FUNCTION
async function clearNotificationCache() {
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    
    try {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Clearing...';
        btn.disabled = true;
        
        const response = await fetch('/admin/alumni-approval/refresh-cache', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Cleared!';
            btn.classList.remove('bg-purple-500', 'hover:bg-purple-600');
            btn.classList.add('bg-green-500');
            
            // Show success message
            alert('✅ Notification cache cleared! Navbar badge should update now.');
            
            // Refresh page after short delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Failed to clear cache');
        }
    } catch (error) {
        console.error('Error clearing cache:', error);
        alert('❌ Failed to clear cache: ' + error.message);
        
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
}
</script>

<!-- Additional CSS to ensure clickability -->
<style>
/* Ensure all elements are clickable */
* {
    pointer-events: auto !important;
}

/* Remove any potential overlays */
body::before,
body::after {
    display: none !important;
}

/* Ensure buttons are fully interactive */
button, a, input, select, textarea {
    pointer-events: auto !important;
    position: relative !important;
    z-index: 1 !important;
}

/* Fix any layout issues */
.main-content-wrapper {
    position: relative !important;
    z-index: 1 !important;
    pointer-events: auto !important;
}
</style>
@endsection