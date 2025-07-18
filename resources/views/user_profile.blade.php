@extends('layouts.app')

@section('content')
<div class="main-card profile-card w-full max-w-6xl p-8 flex flex-wrap lg:flex-nowrap gap-8 bg-orange-100 text-gray-800">
    <!-- Bagian Kiri: Profile Picture & Basic Info -->
    <div class="w-full lg:w-1/3 flex flex-col items-center p-6 bg-orange-200 rounded-lg shadow-inner text-center">
        {{-- Gambar Profil Dinamis --}}
        <div class="w-40 h-40 rounded-full mx-auto bg-gray-300 flex items-center justify-center overflow-hidden mb-4 border-4 border-blue-400">
            {{-- Menggunakan profile_picture dari user jika ada, jika tidak, pakai placeholder dengan inisial --}}
            <img src="{{ $user->profile_picture ? asset($user->profile_picture) : 'https://via.placeholder.com/160/a8dadc/ffffff?text=' . strtoupper(substr($user->full_name, 0, 1)) }}" alt="Profile Picture" class="w-full h-full object-cover">
        </div>
        <h2 class="text-3xl font-bold mb-1 text-orange-700">{{ $user->full_name }}</h2>
        <p class="text-lg text-gray-600">{{ $user->username }} <span class="bg-blue-200 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded-full ml-2">{{ ucfirst($user->role) }}</span></p>
        <p class="text-md text-gray-500 mt-2">{{ $user->prodi ?? 'Prodi belum diisi' }}, {{ $user->fakultas ?? 'Fakultas belum diisi' }}</p>
        <p class="text-md text-gray-500">{{ $user->gender ?? 'Gender belum diisi' }}</p>
        
        {{-- ✅ TAMBAHAN: Social Media Links --}}
        @if($user->hasSocialLinks())
            <div class="mt-4 w-full">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Find me on:</h4>
                <div class="flex justify-center space-x-3">
                    @foreach($user->getFormattedSocialLinks() as $platform => $link)
                        <a href="{{ $link['url'] }}" 
                           target="_blank" 
                           class="flex items-center justify-center w-10 h-10 rounded-full {{ $link['color'] }} text-white transition-all duration-300 hover:transform hover:scale-110 hover:shadow-lg group"
                           title="{{ $link['display'] }}">
                            <i class="{{ $link['icon'] }} group-hover:animate-pulse"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        
        {{-- ✅ TAMBAHAN: Profile Completion Indicator --}}
        <div class="mt-4 w-full">
            @php
                $completion = $user->getProfileCompletionPercentage();
            @endphp
            <div class="text-xs text-gray-600 mb-2 flex items-center justify-center">
                <i class="fas fa-chart-pie mr-1"></i>
                Profile Completion: {{ $completion }}%
            </div>
            <div class="w-full bg-gray-300 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-1000 ease-out" 
                     style="width: {{ $completion }}%"></div>
            </div>
            @if($completion < 100)
                <p class="text-xs text-gray-500 mt-1">{{ 100 - $completion }}% to complete</p>
            @endif
        </div>
        
        <div class="mt-6 flex flex-col items-center space-y-2">
            {{-- ✅ UPDATE: Change route from profile.setup to profile.edit --}}
            <a href="{{ route('profile.edit') }}" 
               class="btn-primary bg-orange-600 hover:bg-orange-700 px-6 py-2 rounded-md transition-all duration-300 flex items-center shadow-lg hover:shadow-xl">
                <i class="fas fa-edit mr-2"></i>Edit Profile
            </a>
            
            {{-- ✅ TAMBAHAN: Quick Action Buttons --}}
            @if(!$user->hasSocialLinks())
                <a href="{{ route('profile.edit') }}" 
                   class="text-blue-600 hover:text-blue-800 text-sm transition-colors flex items-center">
                    <i class="fas fa-plus mr-1"></i>Add Social Links
                </a>
            @endif
            
            <a href="{{ route('home') }}" 
               class="text-gray-600 hover:text-gray-800 text-sm transition-colors flex items-center">
                <i class="fas fa-home mr-1"></i>Back to Home
            </a>
        </div>
    </div>

    <!-- Bagian Kanan: Deskripsi & Interests -->
    <div class="w-full lg:w-2/3 p-6">
        <h3 class="text-xl font-bold mb-4 text-orange-700 flex items-center">
            <i class="fas fa-user-circle mr-2"></i>About Me
        </h3>
        <div class="bg-orange-50 border border-orange-300 rounded-lg p-4 mb-8 text-gray-700 leading-relaxed min-h-[100px] flex items-center justify-center text-center">
            <p>{{ $user->description ?? 'Belum ada deskripsi diri. Klik Edit Profile untuk menambahkan deskripsi.' }}</p>
        </div>

        <h3 class="text-xl font-bold mb-4 text-orange-700 flex items-center">
            <i class="fas fa-heart mr-2"></i>My Interests
        </h3>
        <div class="flex flex-wrap gap-3 mb-8">
            @forelse ($user->interests ?? [] as $interest)
                <span class="bg-blue-500 text-white px-4 py-2 rounded-full flex items-center space-x-2 text-sm font-semibold transition-all duration-300 hover:scale-105 hover:bg-blue-600 shadow-md">
                    @php
                        // ✅ ENHANCED: Map interests to proper icons (same as profile_setup)
                        $interestIcons = [
                            'photography' => 'fas fa-camera',
                            'shopping' => 'fas fa-shopping-bag',
                            'karaoke' => 'fas fa-microphone',
                            'yoga' => 'fas fa-leaf',
                            'cooking' => 'fas fa-utensils',
                            'tennis' => 'fas fa-tennis-ball',
                            'run' => 'fas fa-running',
                            'art' => 'fas fa-palette',
                            'traveling' => 'fas fa-plane-departure',
                            'extreme' => 'fas fa-mountain',
                            'music' => 'fas fa-music',
                            'drink' => 'fas fa-wine-glass',
                            'video_games' => 'fas fa-gamepad',
                        ];
                        $iconClass = $interestIcons[$interest] ?? 'fas fa-tag';
                    @endphp
                    <i class="{{ $iconClass }}"></i>
                    <span>{{ ucfirst(str_replace('_', ' ', $interest)) }}</span>
                </span>
            @empty
                <p class="text-gray-500 text-sm italic">Belum ada minat yang dipilih. <a href="{{ route('profile.edit') }}" class="text-orange-600 hover:text-orange-800 underline transition-colors">Tambah minat</a></p>
            @endforelse
        </div>

        {{-- ✅ TAMBAHAN: Enhanced Profile Information Cards --}}
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <!-- Academic Info Card -->
            <div class="bg-orange-50 border border-orange-300 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-300">
                <h4 class="font-bold text-orange-700 mb-3 flex items-center">
                    <i class="fas fa-graduation-cap mr-2"></i>Academic Info
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Program Studi:</span>
                        <span class="font-medium text-right">{{ $user->prodi ?? 'Not set' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Fakultas:</span>
                        <span class="font-medium text-right">{{ $user->fakultas ?? 'Not set' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Gender:</span>
                        <span class="font-medium">{{ $user->gender ?? 'Not set' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Status:</span>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                            {{ $user->is_verified ? 'Verified' : 'Active' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Account & Social Info Card -->
            <div class="bg-orange-50 border border-orange-300 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-300">
                <h4 class="font-bold text-orange-700 mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>Account Info
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Member since:</span>
                        <span class="font-medium">{{ $user->created_at->format('M Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Role:</span>
                        <span class="font-medium">{{ $user->role_display }}</span>
                    </div>
                    @if($user->hasSocialLinks())
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Social Links:</span>
                            <span class="font-medium flex items-center">
                                {{ $user->getSocialLinksCount() }} connected
                                <i class="fas fa-link ml-1 text-blue-500"></i>
                            </span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Interests:</span>
                        <span class="font-medium">{{ count($user->interests ?? []) }}/3</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ TAMBAHAN: Social Media Links Display (if available) --}}
        @if($user->hasSocialLinks())
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4 mb-6">
                <h4 class="font-bold text-purple-700 mb-3 flex items-center">
                    <i class="fas fa-share-alt mr-2"></i>Social Media
                </h4>
                <div class="grid grid-cols-1 gap-3">
                    @foreach($user->getFormattedSocialLinks() as $platform => $link)
                        <a href="{{ $link['url'] }}" 
                           target="_blank" 
                           class="flex items-center p-3 rounded-lg {{ $link['color'] }} text-white transition-all duration-300 hover:transform hover:scale-105 hover:shadow-lg group">
                            <i class="{{ $link['icon'] }} text-lg mr-3 group-hover:animate-bounce"></i>
                            <span class="font-medium flex-1">{{ $link['display'] }}</span>
                            <i class="fas fa-external-link-alt text-sm opacity-75 group-hover:opacity-100"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ✅ TAMBAHAN: Quick Actions & Profile Completion Hints --}}
        <div class="flex flex-wrap gap-3 items-center justify-between">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('profile.edit') }}" 
                   class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 flex items-center shadow-md hover:shadow-lg">
                    <i class="fas fa-edit mr-2"></i>Edit Profile
                </a>
                
                @if(!$user->hasSocialLinks())
                    <a href="{{ route('profile.edit') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 flex items-center shadow-md hover:shadow-lg">
                        <i class="fas fa-plus mr-2"></i>Add Social Links
                    </a>
                @endif
                
                @if(count($user->interests ?? []) < 3)
                    <a href="{{ route('profile.edit') }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 flex items-center shadow-md hover:shadow-lg">
                        <i class="fas fa-heart mr-2"></i>Add More Interests
                    </a>
                @endif
            </div>
            
            @if($completion < 100)
                <div class="bg-yellow-100 text-yellow-800 px-3 py-2 rounded-lg text-sm font-medium flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    {{ 100 - $completion }}% to complete
                </div>
            @else
                <div class="bg-green-100 text-green-800 px-3 py-2 rounded-lg text-sm font-medium flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Profile Complete!
                </div>
            @endif
        </div>
    </div>
</div>

<!-- FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .transition-all {
        transition: all 0.3s ease;
    }
    
    .transition-transform {
        transition: transform 0.2s ease;
    }
    
    .transition-colors {
        transition: color 0.3s ease;
    }
    
    .transition-shadow {
        transition: box-shadow 0.3s ease;
    }
    
    .hover\:scale-105:hover {
        transform: scale(1.05);
    }
    
    .hover\:scale-110:hover {
        transform: scale(1.1);
    }
    
    .hover\:shadow-lg:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .hover\:shadow-xl:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .group:hover .group-hover\:animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    .group:hover .group-hover\:animate-bounce {
        animation: bounce 1s infinite;
    }
    
    .group:hover .group-hover\:opacity-100 {
        opacity: 1;
    }
    
    /* Custom gradient animation */
    .bg-gradient-to-r {
        background-size: 200% 100%;
        animation: gradientShift 3s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .main-card {
            flex-direction: column;
            padding: 1rem;
        }
        
        .flex-wrap.gap-3 {
            gap: 0.5rem;
        }
        
        .grid.md\:grid-cols-2 {
            grid-template-columns: 1fr;
        }
        
        .justify-between {
            flex-direction: column;
            align-items: stretch;
        }
        
        .justify-between > div {
            margin-bottom: 0.75rem;
        }
    }
    
    /* Loading animation for completion bar */
    @keyframes fillBar {
        from { width: 0%; }
        to { width: var(--completion-width); }
    }
    
    .bg-gradient-to-r.from-blue-500.to-green-500 {
        animation: fillBar 2s ease-out;
    }
</style>

{{-- ✅ TAMBAHAN: Auto-refresh completion on page load --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate completion bar on page load
    const completionBar = document.querySelector('.bg-gradient-to-r.from-blue-500.to-green-500');
    if (completionBar) {
        const completion = {{ $completion }};
        completionBar.style.setProperty('--completion-width', completion + '%');
    }
    
    // Add tooltips to social media links
    const socialLinks = document.querySelectorAll('a[target="_blank"]');
    socialLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            const platform = this.querySelector('i').className.includes('linkedin') ? 'LinkedIn' :
                           this.querySelector('i').className.includes('github') ? 'GitHub' :
                           this.querySelector('i').className.includes('instagram') ? 'Instagram' : 'Social Media';
            this.title = `Visit my ${platform} profile`;
        });
    });
    
    // Add click tracking (optional analytics)
    const editButtons = document.querySelectorAll('a[href*="profile.edit"]');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            console.log('Edit profile clicked');
            // Add analytics tracking here if needed
        });
    });
});
</script>
@endsection