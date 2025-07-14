@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Section --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-full {{ $currentUser->role_badge_color }} flex items-center justify-center text-2xl">
                        {{ $currentUser->role_icon }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Dashboard Moderator</h1>
                        <p class="text-gray-600">Selamat datang, {{ $currentUser->full_name }}</p>
                        <p class="text-sm text-gray-500">
                            <span class="px-2 py-1 rounded-full text-xs {{ $currentUser->role_badge_color }}">
                                {{ $currentUser->role_display }}
                            </span>
                            • Login terakhir: {{ now()->format('d M Y, H:i') }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <a href="{{ route('community') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-comments mr-2"></i>Ke Komunitas
                    </a>
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Komunitas</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $overallStats['total_communities'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-comments text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Pesan</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $overallStats['total_messages'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-calendar-day text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pesan Hari Ini</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $overallStats['messages_today'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-user-friends text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">User Aktif</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $overallStats['active_users'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Communities Section --}}
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-shield-alt text-purple-600 mr-2"></i>
                    Komunitas yang Dikelola
                </h2>
            </div>
            
            @if($communityStats->count() > 0)
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach($communityStats as $item)
                            @php
                                $community = $item['community'];
                                $stats = $item['stats'];
                            @endphp
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 mb-1">{{ $community->name }}</h3>
                                        @if($community->description)
                                            <p class="text-sm text-gray-600 mb-2">{{ Str::limit($community->description, 100) }}</p>
                                        @endif
                                        
                                        {{-- Community Info --}}
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            @if($community->creator_id === $currentUser->id)
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                                    <i class="fas fa-star mr-1"></i>Pembuat
                                                </span>
                                            @endif
                                            @if($community->moderator_id === $currentUser->id)
                                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full">
                                                    <i class="fas fa-shield-alt mr-1"></i>Moderator
                                                </span>
                                            @endif
                                            @if($currentUser->isAdmin())
                                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full">
                                                    <i class="fas fa-crown mr-1"></i>Admin Access
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4">
                                        <a href="{{ route('community', ['group' => $community->id]) }}" 
                                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                            <i class="fas fa-external-link-alt mr-1"></i>Buka
                                        </a>
                                    </div>
                                </div>

                                {{-- Statistics --}}
                                <div class="grid grid-cols-3 gap-3 pt-3 border-t border-gray-100">
                                    <div class="text-center">
                                        <p class="text-lg font-bold text-gray-900">{{ $stats['total_messages'] }}</p>
                                        <p class="text-xs text-gray-600">Total Pesan</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-lg font-bold text-gray-900">{{ $stats['messages_this_week'] }}</p>
                                        <p class="text-xs text-gray-600">Minggu Ini</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-lg font-bold text-gray-900">{{ $stats['unique_posters'] }}</p>
                                        <p class="text-xs text-gray-600">User Aktif</p>
                                    </div>
                                </div>

                                {{-- Activity Level Indicator --}}
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    @php
                                        $activityLevel = $stats['activity_level'];
                                        $activityColors = [
                                            'very_high' => 'bg-green-500',
                                            'high' => 'bg-blue-500',
                                            'medium' => 'bg-yellow-500',
                                            'low' => 'bg-orange-500',
                                            'inactive' => 'bg-gray-400'
                                        ];
                                        $activityLabels = [
                                            'very_high' => 'Sangat Aktif',
                                            'high' => 'Aktif',
                                            'medium' => 'Sedang',
                                            'low' => 'Rendah',
                                            'inactive' => 'Tidak Aktif'
                                        ];
                                    @endphp
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Tingkat Aktivitas:</span>
                                        <span class="px-2 py-1 rounded-full text-xs text-white {{ $activityColors[$activityLevel] }}">
                                            {{ $activityLabels[$activityLevel] }}
                                        </span>
                                    </div>
                                    
                                    @if($stats['last_activity'])
                                        <div class="mt-1">
                                            <span class="text-xs text-gray-500">
                                                Aktivitas terakhir: {{ \Carbon\Carbon::parse($stats['last_activity'])->diffForHumans() }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="p-8 text-center">
                    <div class="text-gray-400 text-4xl mb-4">
                        <i class="fas fa-users-slash"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Komunitas</h3>
                    <p class="text-gray-600 mb-4">
                        @if($currentUser->isAdmin())
                            Sebagai admin, Anda dapat mengelola semua komunitas.
                        @else
                            Belum ada komunitas yang ditugaskan kepada Anda.
                        @endif
                    </p>
                    @if($currentUser->isAdmin())
                        <a href="{{ route('community') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors">
                            <i class="fas fa-plus mr-2"></i>Kelola Komunitas
                        </a>
                    @else
                        <p class="text-sm text-gray-500">Hubungi administrator untuk penugasan komunitas.</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Quick Actions & Tools --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Quick Actions --}}
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        Aksi Cepat
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('community') }}" 
                       class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg mr-3">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Buka Komunitas</p>
                            <p class="text-sm text-gray-600">Lihat dan kelola postingan komunitas</p>
                        </div>
                    </a>

                    @if($currentUser->isAdmin())
                        <a href="#" onclick="showComingSoon('Admin Panel')" 
                           class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="p-2 bg-red-100 text-red-600 rounded-lg mr-3">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Panel Admin</p>
                                <p class="text-sm text-gray-600">Kelola sistem dan pengguna</p>
                            </div>
                        </a>
                    @endif

                    <a href="#" onclick="showComingSoon('Moderator Reports')" 
                       class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="p-2 bg-yellow-100 text-yellow-600 rounded-lg mr-3">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Laporan & Moderasi</p>
                            <p class="text-sm text-gray-600">Kelola laporan dan konten</p>
                        </div>
                    </a>

                    <a href="#" onclick="showComingSoon('Statistics')" 
                       class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="p-2 bg-green-100 text-green-600 rounded-lg mr-3">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Statistik Detail</p>
                            <p class="text-sm text-gray-600">Analisis mendalam aktivitas</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- System Info --}}
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Informasi Sistem
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Role Anda:</span>
                            <span class="px-3 py-1 rounded-full text-sm {{ $currentUser->role_badge_color }}">
                                {{ $currentUser->role_display }}
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Komunitas Dikelola:</span>
                            <span class="font-semibold text-gray-900">{{ $overallStats['total_communities'] }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Status Akun:</span>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                <i class="fas fa-check-circle mr-1"></i>Terverifikasi
                            </span>
                        </div>

                        @if($overallStats['pending_reports'] > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Laporan Pending:</span>
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                                    {{ $overallStats['pending_reports'] }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <h4 class="font-medium text-gray-900 mb-2">Tanggung Jawab Moderator:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Memposting informasi penting</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Memantau aktivitas komunitas</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Merespons laporan user</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Menjaga kualitas konten</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Info --}}
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>Dashboard Moderator • Sistem Komunitas UNUD • {{ now()->format('Y') }}</p>
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

@push('scripts')
<script>
function showComingSoon(feature) {
    showToast(`Fitur ${feature} akan segera tersedia`, 'info');
}

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const typeClasses = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'warning': 'bg-yellow-500 text-white',
        'info': 'bg-blue-500 text-white'
    };

    const typeIcons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };

    toast.className = `flex items-center space-x-3 ${typeClasses[type]} px-4 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 max-w-sm`;
    
    toast.innerHTML = `
        <i class="${typeIcons[type]}"></i>
        <span class="flex-1">${message}</span>
        <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);

    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }, 5000);
}

// Auto-refresh stats every 5 minutes
setInterval(() => {
    location.reload();
}, 300000);
</script>
@endpush

@endsection