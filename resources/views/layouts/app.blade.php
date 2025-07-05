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
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-300 py-4 px-6 flex justify-between items-center text-white sticky top-0 z-50 w-full">
        <div class="text-2xl font-bold">I MATCH U</div>
        <nav class="flex items-center space-x-8"> {{-- Added items-center --}}
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
                <span class="text-gray-400 cursor-not-allowed ml-auto">Logout</span> {{-- Logout link, but disabled --}}
                <div class="w-8 h-8 rounded-full bg-gray-400 ml-4"></div> {{-- Placeholder for profile icon when disabled --}}
            @else
                <a href="{{ route('home') }}" class="hover:text-gray-200">Home</a>
                <a href="{{ route('chat.personal') }}" class="hover:text-gray-200">Chat</a>
                <a href="{{ route('find.people') }}" class="hover:text-gray-200">Find</a>
                <a href="{{ route('community') }}" class="hover:text-gray-200">Community</a>
                <a href="{{ route('user.profile') }}" class="hover:text-gray-200">Profile</a>
                <a href="{{ route('logout') }}" class="ml-auto top-nav-button bg-red-500 hover:bg-red-600">Logout</a> {{-- Logout link, positioned to the right --}}
                {{-- Profile Icon --}}
                <div class="w-8 h-8 rounded-full bg-gray-400 ml-4 flex items-center justify-center overflow-hidden">
                    @auth {{-- Hanya tampilkan jika user login --}}
                        <img src="{{ Auth::user()->profile_picture ?? 'https://via.placeholder.com/32/cccccc/ffffff?text=' . strtoupper(substr(Auth::user()->full_name ?? '', 0, 1)) }}" alt="Profile Icon" class="w-full h-full object-cover">
                    @else
                        {{-- Placeholder jika tidak login --}}
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
</body>
</html>
