<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- DITAMBAHKAN BARIS INI UNTUK CSRF TOKEN --}}
    <title>I Match U</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles here if needed, sesuai UI Anda */
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .hero-background {
            background-image: url('{{ asset('images/unud_background.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        /* Style untuk card utama seperti di UI Anda (untuk Login/Register) */
        .main-card {
            background-color: #343a40;
            color: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            /* Removed position: relative; here as it's often overridden by absolute positioning in child templates */
            z-index: 10;
        }
        .input-field {
            background-color: #495057;
            border: 1px solid #6c757d;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            width: 100%;
            box-sizing: border-box;
        }
        .input-field::placeholder {
            color: #adb5bd;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-outline {
            border: 1px solid #007bff;
            color: #007bff;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s, color 0.2s;
        }
        .btn-outline:hover {
            background-color: #007bff;
            color: white;
        }
        .text-link {
            color: #007bff;
            text-decoration: none;
            transition: color 0.2s;
        }
        .text-link:hover {
            color: #0056b3;
        }
        .top-nav-button {
            background-color: #007bff;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px; /* Full rounded */
            font-weight: 600;
        }

        /* START: Custom styles for Profile Setup Page */
        .main-card.profile-card {
            background-color: #FFF2E8;
            color: #4A4A4A;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .bg-orange-200 { background-color: #FFDAB9; }
        .text-orange-700 { color: #B15E00; }
        .btn-outline.text-orange-700 {
            border-color: #FFC080; /* Border default untuk btn-outline di halaman ini */
        }
        .hover\:bg-orange-700:hover { background-color: #B15E00; }
        .input-field-orange {
            background-color: #FFFAEC;
            border: 1px solid #FFC080;
            color: #4A4A4A;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            box-sizing: border-box;
        }
        .input-field-orange::placeholder { color: #8C8C8C; }
        .btn-primary.bg-orange-600 { background-color: #FF7B00; }
        .btn-primary.bg-orange-600:hover { background-color: #E66A00; }

        /* **DITAMBAHKAN/DIREVISI: CSS untuk tombol minat yang dipilih** */
        .btn-outline[data-interest].selected {
            background-color: #ff7b00 !important;
            color: white !important;
            border-color: #ff7b00 !important;
        }
        .btn-outline[data-interest].selected i {
            color: white !important;
        }

        /* END: Custom styles for Profile Setup Page */

        /* START: Custom styles for Match Setup Page */
        .main-card.bg-blue-100 { background-color: #E0F2F7; color: #212529; }
        .text-blue-700 { color: #0056b3; }
        .btn-match-category {
            background-color: #f0f8ff;
            border: 1px solid #a8dadc;
            color: #1d3557;
            padding: 1.5rem 1rem;
            border-radius: 0.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: background-color 0.2s, border-color 0.2s, color 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .btn-match-category:hover { background-color: #a8dadc; color: white; border-color: #1d3557; }
        .btn-match-category i { color: #457b9d; }
        .btn-match-category:hover i { color: white; }
        .btn-primary.bg-blue-600 { background-color: #007bff; }
        .btn-primary.bg-blue-600:hover { background-color: #0056b3; }
        .btn-match-category.selected { background-color: #007bff; color: white; border-color: #007bff; }
        .btn-match-category.selected i { color: white; }
        /* END: Custom styles for Match Setup Page */

        /* START: Custom styles for Finding People Page */
        .profile-card-swipe {
            border: 1px solid rgba(0, 0, 0, 0.1);
            position: relative;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
        }
        /* END: Custom styles for Finding People Page */

        /* START: Custom styles for Personal Chat Page */
        .input-field-chat {
            background-color: #f0f2f5;
            border: 1px solid #e0e0e0;
            color: #212529;
            padding: 0.75rem 1rem;
            border-radius: 9999px;
            width: 100%;
            box-sizing: border-box;
        }
        .input-field-chat::placeholder { color: #6c757d; }
        .chat-list-item { cursor: pointer; text-decoration: none; }
        .chat-list-item:hover { /* Styling untuk hover sudah ada dari bg-gray-100/blue-200 */ }
        /* END: Custom styles for Personal Chat Page */

        /* START: Custom styles for Home/Feed Page */
        .main-content-area {
            background-color: #f8f9fa;
        }
        .post-card {
            border: 1px solid #e9ecef;
        }
        /* END: Custom styles for Home/Feed Page */

        /* **PENTING: CSS untuk fixed header/footer dan scrollable content** */
        .main-content-wrapper {
            overflow-y: auto;
            padding: 1rem;
            flex-grow: 1;
        }

        /* START: Custom styles for Community Chat Page */
        .community-circle {
            width: 72px;
            height: 72px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .community-circle img {
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        .community-circle.active img,
        .community-circle:hover img {
            border-color: #007bff;
        }
        .community-post-card {
            border: 1px solid #e9ecef;
        }
        .write-post-area textarea {
            background-color: #f8f9fa;
        }

        /* Utility untuk menyembunyikan scrollbar di Webkit browsers */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        /* END: Custom styles for Community Chat Page */

        /* ✅ NEW: Admin Dropdown Styles */
        .admin-dropdown {
            transform-origin: top right;
        }
        .admin-dropdown.show {
            animation: dropdownFadeIn 0.2s ease-out;
        }
        .admin-dropdown.hide {
            animation: dropdownFadeOut 0.2s ease-in;
        }
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        @keyframes dropdownFadeOut {
            from {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
            to {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-300 py-4 px-6 flex justify-between items-center text-white sticky top-0 z-50 w-full">
        <div class="text-2xl font-bold">I MATCH U</div>
        <nav class="flex items-center space-x-8">
            {{-- Simulasi disabled nav links for profile setup/match setup --}}
            @php
                $isProfileIncomplete = false;
                if (Auth::check()) {
                    $user = Auth::user();
                    $requiredProfileFields = ['prodi', 'fakultas', 'gender', 'description'];

                    foreach ($requiredProfileFields as $field) {
                        if (empty($user->$field)) {
                            $isProfileIncomplete = true;
                            break;
                        }
                    }
                    if (empty($user->interests) || !is_array($user->interests) || count($user->interests) === 0) {
                        $isProfileIncomplete = true;
                    }
                }
                // DITAMBAHKAN/DIREVISI: Kondisi untuk menonaktifkan navbar
                // Navbar dinonaktifkan HANYA JIKA profil belum lengkap DAN sedang di halaman setup/match setup
                $shouldDisableNavbar = $isProfileIncomplete && (request()->routeIs('profile.setup') || request()->routeIs('match.setup'));
            @endphp

            @if ($shouldDisableNavbar)
                {{-- Disabled links if profile incomplete and on setup/match pages --}}
                <span class="text-gray-400 cursor-not-allowed">Home</span>
                <span class="text-gray-400 cursor-not-allowed">Chat</span>
                <span class="text-gray-400 cursor-not-allowed">Find</span>
                <span class="text-gray-400 cursor-not-allowed">Community</span>
                <span class="text-gray-400 cursor-not-allowed">Profile</span>
                <span class="text-gray-400 cursor-not-allowed ml-auto">Logout</span>
                <div class="w-8 h-8 rounded-full bg-gray-400 ml-4"></div>
            @else
                <a href="{{ route('home') }}" class="hover:text-gray-200">Home</a>
                <a href="{{ route('chat.personal') }}" class="hover:text-gray-200">Chat</a>
                <a href="{{ route('find.people') }}" class="hover:text-gray-200">Find</a>
                <a href="{{ route('community') }}" class="hover:text-gray-200">Community</a>
                <a href="{{ route('submissions.index') }}" class="hover:text-gray-200">Submit Konten</a>
                
                {{-- ✅ ENHANCED: Admin Management Dropdown - GANTI YANG LAMA --}}
                @auth
                    @if(auth()->user()->isAdmin())
                        <div class="relative group">
                            <button class="hover:text-gray-200 flex items-center space-x-1 group-hover:text-gray-200" 
                                    onclick="toggleAdminDropdown()" 
                                    id="adminDropdownBtn">
                                <span>Admin Panel</span>
                                <i class="fas fa-chevron-down text-xs transition-transform duration-200" id="adminDropdownIcon"></i>
                                {{-- Badge untuk notifikasi gabungan --}}
                                @php
                                    $totalPending = 0;
                                    
                                    // Hitung pending payments
                                    try {
                                        $pendingPayments = cache()->remember('admin_pending_payments_' . auth()->id(), 60, function() {
                                            return \App\Models\Payment::where('status', 'pending')->count();
                                        });
                                        $totalPending += $pendingPayments;
                                    } catch (\Exception $e) {
                                        $pendingPayments = 0;
                                    }
                                    
                                    // Hitung pending submissions
                                    try {
                                        $pendingSubmissions = cache()->remember('admin_pending_submissions_' . auth()->id(), 60, function() {
                                            return \App\Models\ContentSubmission::where('status', 'pending_approval')->count();
                                        });
                                        $totalPending += $pendingSubmissions;
                                    } catch (\Exception $e) {
                                        $pendingSubmissions = 0;
                                    }
                                    
                                    // Hitung pending alumni (existing code)
                                    $pendingAlumni = 0;
                                    try {
                                        $cacheKey = 'alumni_pending_count_' . auth()->id();
                                        $pendingAlumni = cache()->remember($cacheKey, 60, function() {
                                            $startTime = microtime(true);
                                            $count = \Illuminate\Support\Facades\DB::table('users')
                                                ->where('role', 'alumni')
                                                ->where('is_verified', 0)
                                                ->limit(15)
                                                ->count();
                                            return min($count, 99);
                                        });
                                        $totalPending += $pendingAlumni;
                                    } catch (\Exception $e) {
                                        $pendingAlumni = 0;
                                    }
                                @endphp
                                
                                @if($totalPending > 0)
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold">
                                        {{ $totalPending > 99 ? '99+' : $totalPending }}
                                    </span>
                                @endif
                            </button>
                            
                            {{-- Dropdown Menu --}}
                            <div class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-50 hidden opacity-0 transform scale-95 transition-all duration-200 admin-dropdown" 
                                 id="adminDropdown">
                                <div class="py-2">
                                    {{-- Header --}}
                                    <div class="px-4 py-2 border-b border-gray-200">
                                        <h3 class="font-semibold text-gray-800">Admin Management</h3>
                                        <p class="text-sm text-gray-600">Kelola sistem IMU</p>
                                    </div>
                                    
                                    {{-- Dashboard Terintegrasi --}}
                                    <a href="{{ route('admin.dashboard.index') }}" 
                                       class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 text-gray-700 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-tachometer-alt text-blue-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium">Dashboard Admin</p>
                                                <p class="text-sm text-gray-500">Kelola payment & submission</p>
                                            </div>
                                        </div>
                                        @if(($pendingPayments + $pendingSubmissions) > 0)
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-semibold">
                                                {{ $pendingPayments + $pendingSubmissions }}
                                            </span>
                                        @endif
                                    </a>

                                    {{-- Payment Management --}}
                                    <a href="{{ route('admin.payments.index') }}" 
                                       class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 text-gray-700 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-credit-card text-green-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium">Kelola Pembayaran</p>
                                                <p class="text-sm text-gray-500">Konfirmasi & verifikasi</p>
                                            </div>
                                        </div>
                                        @if($pendingPayments > 0)
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-semibold">
                                                {{ $pendingPayments }}
                                            </span>
                                        @endif
                                    </a>
                                    
                                    {{-- Submission Management --}}
                                    <a href="{{ route('admin.submissions.index') }}" 
                                       class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 text-gray-700 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-tasks text-purple-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium">Kelola Submission</p>
                                                <p class="text-sm text-gray-500">Review & publikasi</p>
                                            </div>
                                        </div>
                                        @if($pendingSubmissions > 0)
                                            <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full font-semibold">
                                                {{ $pendingSubmissions }}
                                            </span>
                                        @endif
                                    </a>
                                    
                                    {{-- Alumni Approval --}}
                                    <a href="{{ route('admin.alumni-approval.index') }}" 
                                       class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 text-gray-700 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-user-graduate text-blue-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium">Alumni Approval</p>
                                                <p class="text-sm text-gray-500">Verifikasi alumni</p>
                                            </div>
                                        </div>
                                        @if($pendingAlumni > 0)
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-semibold">
                                                {{ $pendingAlumni }}
                                            </span>
                                        @endif
                                    </a>
                                    
                                    {{-- Divider --}}
                                    <div class="border-t border-gray-200 my-2"></div>
                                    
                                    {{-- Quick Stats --}}
                                    <div class="px-4 py-2">
                                        <p class="text-sm font-medium text-gray-800 mb-2">Status Cepat</p>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div class="bg-yellow-50 p-2 rounded">
                                                <p class="text-yellow-800 font-medium">{{ $pendingPayments + $pendingSubmissions }}</p>
                                                <p class="text-yellow-600">Pending Review</p>
                                            </div>
                                            <div class="bg-green-50 p-2 rounded">
                                                <p class="text-green-800 font-medium">{{ cache()->get('admin_total_approved_today', 0) }}</p>
                                                <p class="text-green-600">Approved Hari Ini</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endauth
                
                <a href="{{ route('user.profile') }}" class="hover:text-gray-200">Profile</a>
                
                {{-- ✅ NOTIFICATION BELL - NEW! --}}
                @auth
                <div class="relative">
                    <button id="notification-bell" class="relative p-2 hover:bg-blue-400 rounded-full transition-colors">
                        <i class="fas fa-bell text-xl"></i>
                        {{-- Red dot indicator --}}
                        <span id="notification-dot" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-xs flex items-center justify-center text-white font-bold hidden">
                            <span id="notification-count">0</span>
                        </span>
                    </button>
                    
                    {{-- Notification Dropdown --}}
                    <div id="notification-dropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 text-gray-800 hidden z-50">
                        {{-- Header --}}
                        <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">Notifications</h3>
                            <button id="mark-all-read" class="text-blue-500 text-sm hover:text-blue-700">
                                Mark all read
                            </button>
                        </div>
                        
                        {{-- Notifications List --}}
                        <div id="notifications-list" class="max-h-96 overflow-y-auto">
                            {{-- Notifications will be loaded here via JavaScript --}}
                            <div class="p-4 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin text-lg mb-2"></i>
                                <p>Loading notifications...</p>
                            </div>
                        </div>
                        
                        {{-- Footer --}}
                        <div class="px-4 py-3 border-t border-gray-200 text-center">
                            <a href="{{ route('notifications.index') }}" class="text-blue-500 text-sm hover:text-blue-700">
                                View all notifications
                            </a>
                        </div>
                    </div>
                </div>
                @endauth
                
                <a href="{{ route('logout') }}" class="ml-auto top-nav-button bg-red-500 hover:bg-red-600">Logout</a>
                
                {{-- Profile Icon --}}
                <div class="w-8 h-8 rounded-full bg-gray-400 ml-4 flex items-center justify-center overflow-hidden">
                    @auth
                        <img src="{{ Auth::user()->profile_picture ?? 'https://via.placeholder.com/32/cccccc/ffffff?text=' . strtoupper(substr(Auth::user()->full_name ?? '', 0, 1)) }}" alt="Profile Icon" class="w-full h-full object-cover">
                    @else
                        <i class="fas fa-user text-white text-lg"></i>
                    @endauth
                </div>
            @endif
        </nav>
    </header>

    <main class="flex-grow flex items-center justify-center p-4 relative hero-background">
        <div class="absolute inset-0 bg-black opacity-50 z-0"></div>
        @yield('content')
    </main>

    <footer class="bg-blue-300 py-4 px-6 text-white text-sm flex justify-between items-center">
        <div class="text-gray-200">"For Active Udayana University Students Only"</div>
        <div class="space-x-4">
            <a href="#" class="hover:text-gray-200">IMISSU</a>
            <a href="#" class="hover:text-gray-200">SIC Home</a>
            <a href="#" class="hover:text-gray-200">Contact Us</a>
            <a href="#" class="hover:text-gray-200">Guidelines</a>
        </div>
    </footer>

    @stack('scripts') {{-- Pastikan ini ada untuk menampung script dari halaman child --}}

    {{-- ✅ ADMIN DROPDOWN JAVASCRIPT --}}
    <script>
        function toggleAdminDropdown() {
            const dropdown = document.getElementById('adminDropdown');
            const icon = document.getElementById('adminDropdownIcon');
            
            if (dropdown.classList.contains('hidden')) {
                // Show dropdown
                dropdown.classList.remove('hidden');
                setTimeout(() => {
                    dropdown.classList.remove('opacity-0', 'scale-95');
                    dropdown.classList.add('opacity-100', 'scale-100');
                }, 10);
                icon.classList.add('rotate-180');
            } else {
                // Hide dropdown  
                dropdown.classList.remove('opacity-100', 'scale-100');
                dropdown.classList.add('opacity-0', 'scale-95');
                icon.classList.remove('rotate-180');
                setTimeout(() => {
                    dropdown.classList.add('hidden');
                }, 200);
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('adminDropdown');
            const button = document.getElementById('adminDropdownBtn');
            
            if (dropdown && button && !button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('opacity-100', 'scale-100');
                dropdown.classList.add('opacity-0', 'scale-95');
                const icon = document.getElementById('adminDropdownIcon');
                if (icon) icon.classList.remove('rotate-180');
                setTimeout(() => {
                    dropdown.classList.add('hidden');
                }, 200);
            }
        });
    </script>

    {{-- ✅ NOTIFICATION JAVASCRIPT - OPTIMIZED --}}
    @auth
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationBell = document.getElementById('notification-bell');
            const notificationDropdown = document.getElementById('notification-dropdown');
            const notificationDot = document.getElementById('notification-dot');
            const notificationCount = document.getElementById('notification-count');
            const notificationsList = document.getElementById('notifications-list');
            const markAllReadBtn = document.getElementById('mark-all-read');
            
            let dropdownOpen = false;
            let requestInProgress = false; // ✅ Prevent multiple concurrent requests

            // Toggle notification dropdown
            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownOpen = !dropdownOpen;
                
                if (dropdownOpen) {
                    notificationDropdown.classList.remove('hidden');
                    loadNotifications();
                } else {
                    notificationDropdown.classList.add('hidden');
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                if (dropdownOpen) {
                    notificationDropdown.classList.add('hidden');
                    dropdownOpen = false;
                }
            });

            // Prevent dropdown from closing when clicking inside
            notificationDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Mark all as read
            markAllReadBtn.addEventListener('click', function() {
                markAllNotificationsAsRead();
            });

            // Load notification count on page load
            updateNotificationCount();
            
            // Poll for new notifications every 30 seconds
            setInterval(updateNotificationCount, 30000);

            // Functions
            async function updateNotificationCount() {
                if (requestInProgress) return; // ✅ Prevent concurrent requests
                
                try {
                    requestInProgress = true;
                    const controller = new AbortController(); // ✅ Add timeout support
                    const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
                    
                    const response = await fetch('/notifications/count', {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        const count = data.unread_count;
                        
                        if (count > 0) {
                            notificationDot.classList.remove('hidden');
                            notificationCount.textContent = count > 99 ? '99+' : count;
                        } else {
                            notificationDot.classList.add('hidden');
                        }
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Error fetching notification count:', error);
                    }
                } finally {
                    requestInProgress = false;
                }
            }

            async function loadNotifications() {
                if (requestInProgress) return; // ✅ Prevent concurrent requests
                
                try {
                    requestInProgress = true;
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 8000); // 8 second timeout
                    
                    const response = await fetch('/notifications', {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        renderNotifications(data.notifications);
                        updateNotificationCount(); // Update count after loading
                    }
                } catch (error) {
                    if (error.name === 'AbortError') {
                        notificationsList.innerHTML = '<div class="p-4 text-center text-orange-500">Loading timed out, please try again</div>';
                    } else {
                        console.error('Error loading notifications:', error);
                        notificationsList.innerHTML = '<div class="p-4 text-center text-red-500">Error loading notifications</div>';
                    }
                } finally {
                    requestInProgress = false;
                }
            }

            function renderNotifications(notifications) {
                if (notifications.length === 0) {
                    notificationsList.innerHTML = `
                        <div class="p-6 text-center text-gray-500">
                            <i class="fas fa-bell-slash text-2xl mb-2"></i>
                            <p>No notifications yet</p>
                        </div>
                    `;
                    return;
                }

                const notificationsHtml = notifications.map(notification => {
                    const isUnread = !notification.is_read;
                    const bgClass = isUnread ? 'bg-blue-50' : 'bg-white';
                    const borderClass = isUnread ? 'border-l-4 border-blue-500' : '';
                    
                    let actionButton = '';
                    if (notification.type === 'like_received' && isUnread) {
                        actionButton = `
                            <button onclick="likeBack(${notification.id}, ${notification.from_user.id})" 
                                    class="mt-2 bg-pink-500 hover:bg-pink-600 text-white px-3 py-1 rounded-full text-xs transition-colors">
                                💖 Like Back
                            </button>
                        `;
                    } else if (notification.type === 'match_created') {
                        actionButton = `
                            <button onclick="goToChat(${notification.data.other_user.id})" 
                                    class="mt-2 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-full text-xs transition-colors">
                                💬 Start Chat
                            </button>
                        `;
                    }

                    return `
                        <div class="p-4 hover:bg-gray-50 transition-colors ${bgClass} ${borderClass}" 
                             onclick="markAsRead(${notification.id})">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
                                    <img src="${notification.from_user?.profile_picture || 'https://via.placeholder.com/40/cccccc/ffffff?text=' + (notification.from_user?.name?.charAt(0) || 'U')}" 
                                         alt="Profile" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm text-gray-800">${notification.title}</p>
                                    <p class="text-sm text-gray-600 mt-1">${notification.message}</p>
                                    <p class="text-xs text-gray-400 mt-1">${notification.created_at}</p>
                                    ${actionButton}
                                </div>
                                ${isUnread ? '<div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>' : ''}
                            </div>
                        </div>
                    `;
                }).join('');

                notificationsList.innerHTML = notificationsHtml;
            }

            // Global functions for button actions (with throttling)
            let actionInProgress = false;
            
            window.markAsRead = async function(notificationId) {
                if (actionInProgress) return;
                
                try {
                    actionInProgress = true;
                    await fetch('/notifications/mark-read', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ notification_id: notificationId })
                    });
                    
                    updateNotificationCount();
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                } finally {
                    actionInProgress = false;
                }
            };

            window.likeBack = async function(notificationId, fromUserId) {
                if (actionInProgress) return;
                
                try {
                    actionInProgress = true;
                    const response = await fetch('/notifications/like-back', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ 
                            notification_id: notificationId,
                            from_user_id: fromUserId 
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        if (data.matched) {
                            // Show match notification
                            alert('🎉 IT\'S A MATCH! Check your chat!');
                        } else {
                            alert('💕 Like sent back!');
                        }
                        
                        loadNotifications(); // Reload notifications
                        updateNotificationCount();
                    }
                } catch (error) {
                    console.error('Error liking back:', error);
                } finally {
                    actionInProgress = false;
                }
            };

            window.goToChat = function(userId) {
                window.location.href = `/chat/personal?with=${userId}`;
            };

            async function markAllNotificationsAsRead() {
                if (actionInProgress) return;
                
                try {
                    actionInProgress = true;
                    await fetch('/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    loadNotifications();
                    updateNotificationCount();
                } catch (error) {
                    console.error('Error marking all notifications as read:', error);
                } finally {
                    actionInProgress = false;
                }
            }
        });
    </script>
    @endauth
</body>
</html>