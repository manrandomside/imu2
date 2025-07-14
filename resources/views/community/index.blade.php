@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-4xl h-[600px] flex flex-col bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">

    {{-- ✅ ENHANCED: TOP SECTION dengan Role Display --}}
    <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
        {{-- User Role Info --}}
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full {{ $currentUser->role_badge_color }} flex items-center justify-center">
                    <span class="text-lg">{{ $currentUser->role_icon }}</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">{{ $currentUser->full_name }}</p>
                    <p class="text-sm text-gray-600">
                        <span class="px-2 py-1 rounded-full text-xs {{ $currentUser->role_badge_color }}">
                            {{ $currentUser->role_display }}
                        </span>
                        @if($currentUser->isModerator())
                            • {{ $currentUser->moderatedCommunities->count() }} komunitas ditugaskan
                        @elseif($currentUser->isAdmin())
                            • Akses penuh semua komunitas
                        @else
                            • Akses baca semua komunitas
                        @endif
                    </p>
                </div>
            </div>
            
            {{-- ✅ NEW: Admin Panel Button --}}
            @if($currentUser->isAdmin())
                <button onclick="openAdminPanel()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-sm transition-colors">
                    <i class="fas fa-cog mr-1"></i> Admin Panel
                </button>
            @endif
        </div>

        {{-- Communities List --}}
        <div class="flex space-x-4 overflow-x-auto pb-2 scrollbar-hide">
            @forelse ($communities as $community)
                <a href="{{ route('community', ['group' => $community->id]) }}" 
                   class="community-circle flex-shrink-0 text-center transition-all duration-200 hover:transform hover:scale-105 {{ ($selectedGroup && $selectedGroup->id === $community->id) ? 'border-2 border-blue-600 p-1 rounded-full' : '' }}">
                    <div class="relative w-16 h-16 rounded-full mx-auto mb-1 flex items-center justify-center text-2xl {{ ($selectedGroup && $selectedGroup->id === $community->id) ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                        {{-- Community Icon --}}
                        @switch($community->name)
                            @case('Pengumuman Umum')
                                📢
                                @break
                            @case('Info Beasiswa')
                                🎓
                                @break
                            @case('Lowongan Kerja')
                                💼
                                @break
                            @case('Event & Workshop')
                                🎪
                                @break
                            @case('PKM & Kompetisi')
                                🏆
                                @break
                            @case('Info Akademik')
                                📚
                                @break
                            @case('PKM')
                                🏆
                                @break
                            @case('Lomba')
                                🏆
                                @break
                            @case('Workshop')
                                🛠️
                                @break
                            @case('Seminar')
                                🎤
                                @break
                            @default
                                💬
                        @endswitch
                        
                        {{-- ✅ KEEP: Moderator Indicator (Green dot only) --}}
                        @if($community->user_permissions['can_post'] ?? false)
                            <div class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                @if($currentUser->isAdmin())
                                    <span class="text-xs text-white">👑</span>
                                @else
                                    <span class="text-xs text-white">✏️</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <p class="text-xs font-semibold {{ ($selectedGroup && $selectedGroup->id === $community->id) ? 'text-blue-800' : 'text-gray-700' }}">
                        {{ Str::limit($community->name, 12) }}
                    </p>
                </a>
            @empty
                <div class="flex items-center justify-center w-full py-8">
                    <div class="text-center">
                        <div class="text-4xl mb-2">🏗️</div>
                        <p class="text-gray-500 text-sm">Tidak ada komunitas yang tersedia.</p>
                        <p class="text-gray-400 text-xs mt-1">Hubungi admin untuk membuat komunitas baru.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ✅ ENHANCED: MAIN SECTION dengan Permission Info --}}
    <div class="flex flex-col flex-grow p-4 overflow-y-auto bg-gray-100" id="messages-container">
        @if($selectedGroup)
            {{-- ✅ ENHANCED: Group Header dengan Detailed Info --}}
            <div class="bg-white rounded-lg p-4 mb-4 border border-gray-200">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h4 class="font-bold text-xl text-gray-900">📋 {{ $selectedGroup->name }}</h4>
                            <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                                {{ $groupMessages->count() }} {{ $groupMessages->count() === 1 ? 'post' : 'posts' }}
                            </span>
                        </div>


                        
                        
                        {{-- ✅ NEW: Group Management Info --}}
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            @if($selectedGroup->creator)
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                    <i class="fas fa-user-plus"></i> Dibuat: {{ $selectedGroup->creator->full_name }}
                                </span>
                            @endif
                            
                            @if($selectedGroup->moderator)
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full">
                                    <i class="fas fa-shield-alt"></i> Moderator: {{ $selectedGroup->moderator->full_name }}
                                </span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full">
                                    <i class="fas fa-exclamation-triangle"></i> Tanpa moderator
                                </span>
                            @endif
                            
                            {{-- ✅ NEW: User Permission Display --}}
                            @if($selectedGroup->user_permissions['can_post'] ?? false)
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full">
                                    <i class="fas fa-edit"></i> Dapat posting
                                </span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-600 rounded-full">
                                    <i class="fas fa-eye"></i> Hanya baca
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- ✅ NEW: Admin Controls --}}
                    @if($currentUser->isAdmin())
                        <div class="flex items-center space-x-2">
                            <button onclick="openModeratorAssignment({{ $selectedGroup->id }})" 
                                    class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 rounded text-xs transition-colors">
                                <i class="fas fa-user-shield"></i> Kelola Moderator
                            </button>
                            <button onclick="openGroupSettings({{ $selectedGroup->id }})" 
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs transition-colors">
                                <i class="fas fa-cog"></i> Pengaturan
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Group Description --}}
                @if($selectedGroup->description)
                    <div class="mt-3 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
                        <p class="text-sm text-blue-800">{{ $selectedGroup->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Messages Display --}}
            <div id="messages-list">
                @forelse ($groupMessages as $message)
                    <div class="community-post-card bg-white p-4 rounded-lg shadow-md mb-4 border-l-4 border-blue-400" data-message-id="{{ $message->id }}">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 rounded-full bg-blue-500 mr-3 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                @if($message->sender->profile_picture)
                                    <img src="{{ $message->sender->profile_picture }}" alt="User Avatar" class="w-full h-full object-cover">
                                @else
                                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($message->sender->full_name ?? 'A', 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <p class="font-semibold text-gray-800">{{ $message->sender->full_name }}</p>
                                    
                                    {{-- ✅ ENHANCED: Role Badge dengan Icon --}}
                                    <span class="flex items-center space-x-1 {{ $message->sender->role_badge_color }} text-xs font-semibold px-2 py-0.5 rounded-full">
                                        <span>{{ $message->sender->role_icon }}</span>
                                        <span>{{ $message->sender->role_display }}</span>
                                    </span>
                                    
                                    {{-- ✅ NEW: Special Badges --}}
                                    @if($message->sender->isAdmin())
                                        <span class="text-red-500 text-sm" title="Administrator">👑</span>
                                    @elseif($message->sender->isModerator() && $selectedGroup->moderator_id === $message->sender->id)
                                        <span class="text-purple-500 text-sm" title="Moderator Grup Ini">🛡️</span>
                                    @endif
                                    
                                    @if($selectedGroup->creator_id === $message->sender->id)
                                        <span class="text-blue-500 text-sm" title="Pembuat Grup">⭐</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500">{{ $message->created_at->format('d M Y, H:i') }} • {{ $message->created_at->diffForHumans() }}</p>
                            </div>
                            
                            {{-- ✅ NEW: Message Actions untuk Moderator/Admin --}}
                            @if($selectedGroup->user_permissions['can_moderate'] ?? false)
                                <div class="relative">
                                    <button onclick="toggleMessageActions({{ $message->id }})" class="text-gray-400 hover:text-gray-600 p-1">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div id="message-actions-{{ $message->id }}" class="absolute right-0 top-8 bg-white border border-gray-200 rounded-lg shadow-lg py-1 min-w-[120px] hidden z-10">
                                        <button onclick="editMessage({{ $message->id }})" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-edit mr-2"></i>Edit
                                        </button>
                                        <button onclick="deleteMessage({{ $message->id }})" class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <i class="fas fa-trash mr-2"></i>Hapus
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="prose prose-sm max-w-none">
                            <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $message->message_content }}</p>
                        </div>

                        {{-- File Attachment Display --}}
                        @if($message->hasAttachment())
                            <div class="mt-3 p-3 bg-gray-50 rounded-lg border">
                                <div class="flex items-center space-x-2">
                                    <i class="{{ $message->attachment_icon }} {{ $message->attachment_color }}"></i>
                                    <a href="{{ $message->attachment_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        {{ $message->attachment_name }}
                                    </a>
                                    <span class="text-gray-400 text-xs">{{ $message->formatted_file_size }}</span>
                                </div>
                                @if($message->isImageAttachment())
                                    <div class="mt-2">
                                        <img src="{{ $message->attachment_url }}" alt="Attachment" class="max-w-full h-auto rounded-lg border max-h-64 object-cover">
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Interaction Buttons --}}
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center space-x-4 text-gray-500 text-sm">
                                @php
                                    $reactionCounts = $message->getReactionCounts();
                                    $userReaction = Auth::user() ? $message->getUserReactionType(Auth::id()) : null;
                                @endphp
                                
                                <button onclick="toggleReaction({{ $message->id }}, 'like')" 
                                        class="flex items-center space-x-1 hover:text-red-500 transition-colors {{ $userReaction === 'like' ? 'text-red-500' : 'text-gray-500' }}">
                                    <i class="fas fa-heart"></i>
                                    <span class="reaction-count-like">{{ $reactionCounts['like'] ?? 0 }}</span>
                                </button>

                                <button onclick="toggleReaction({{ $message->id }}, 'thumbs_up')" 
                                        class="flex items-center space-x-1 hover:text-blue-500 transition-colors {{ $userReaction === 'thumbs_up' ? 'text-blue-500' : 'text-gray-500' }}">
                                    <i class="fas fa-thumbs-up"></i>
                                    <span class="reaction-count-thumbs_up">{{ $reactionCounts['thumbs_up'] ?? 0 }}</span>
                                </button>

                                <button onclick="toggleCommentBox({{ $message->id }})" 
                                        class="flex items-center space-x-1 hover:text-blue-500 transition-colors">
                                    <i class="fas fa-comment"></i>
                                    <span class="comment-count">{{ $message->total_comments }}</span>
                                </button>
                            </div>
                            <button class="text-gray-400 hover:text-blue-500 transition-colors text-sm">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>

                        {{-- Comments Section --}}
                        <div class="comments-section-{{ $message->id }} hidden mt-3 pt-3 border-t border-gray-100">
                            <div class="comments-list-{{ $message->id }} space-y-2 mb-3">
                                @foreach($message->getTopLevelComments(3) as $comment)
                                    <div class="flex space-x-2 p-2 bg-gray-50 rounded-lg">
                                        <div class="w-6 h-6 rounded-full bg-blue-500 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                            @if($comment->user->profile_picture)
                                                <img src="{{ $comment->user->profile_picture }}" alt="User Avatar" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-white font-bold text-xs">{{ strtoupper(substr($comment->user->full_name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-semibold text-sm text-gray-800">{{ $comment->user->full_name }}</span>
                                                <span class="text-xs text-gray-500">{{ $comment->created_at->format('H:i A, d M Y') }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 mt-1">{{ $comment->comment_content }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="flex space-x-2">
                                <input type="text" 
                                       class="comment-input flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" 
                                       placeholder="Tulis komentar..."
                                       data-message-id="{{ $message->id }}">
                                <button onclick="submitComment({{ $message->id }})" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                    Kirim
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex items-center justify-center flex-1">
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📭</div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum ada postingan</h3>
                            <p class="text-gray-500 text-sm max-w-md mx-auto">
                                Komunitas <strong>{{ $selectedGroup->name }}</strong> belum memiliki postingan. 
                                @if($selectedGroup->user_permissions['can_post'] ?? false)
                                    Buat postingan pertama di bawah!
                                @else
                                    {{ $selectedGroup->user_permissions['posting_reason'] ?? 'Tunggu admin atau moderator untuk memposting informasi.' }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        @else
            <div class="flex items-center justify-center flex-1">
                <div class="text-center">
                    <div class="text-6xl mb-4">🎯</div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Pilih Komunitas</h3>
                    <p class="text-gray-500 text-sm">Pilih salah satu komunitas di atas untuk melihat postingan terbaru.</p>
                </div>
            </div>
        @endif

        {{-- ✅ ENHANCED: Post Writing Area dengan Clear Permission Info --}}
        @if ($selectedGroup && ($selectedGroup->user_permissions['can_post'] ?? false))
        <div class="write-post-area bg-white p-4 rounded-lg shadow-md mt-4 border-2 border-dashed border-green-300">
            <form id="community-post-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="selected-group-id" value="{{ $selectedGroup->id }}">
                
                <div class="flex items-start space-x-3 mb-3">
                    <div class="w-8 h-8 rounded-full {{ $currentUser->role_badge_color }} flex-shrink-0 flex items-center justify-center">
                        @if($currentUser->profile_picture)
                            <img src="{{ $currentUser->profile_picture }}" alt="Your Avatar" class="w-full h-full object-cover rounded-full">
                        @else
                            <span class="text-white font-bold text-sm">{{ strtoupper(substr($currentUser->full_name ?? 'U', 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-700">
                            Buat postingan sebagai 
                            <span class="inline-flex items-center space-x-1 {{ $currentUser->role_badge_color }} px-2 py-1 rounded-full text-xs">
                                <span>{{ $currentUser->role_icon }}</span>
                                <span>{{ $currentUser->role_display }}</span>
                            </span>
                        </p>
                        <p class="text-xs text-gray-500">
                            @if($currentUser->isAdmin())
                                Akses posting ke semua komunitas
                            @elseif($currentUser->isModerator())
                                Akses posting ke komunitas yang ditugaskan
                            @endif
                            • Postingan akan terlihat oleh semua anggota komunitas {{ $selectedGroup->name }}
                        </p>
                    </div>
                </div>
                
                <textarea id="group-message-input" 
                          name="message_content"
                          class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent resize-none transition-all" 
                          rows="4" 
                          placeholder="Tulis pengumuman atau informasi penting untuk komunitas..."
                          maxlength="5000"></textarea>
                
                <div id="char-counter" class="text-xs text-gray-500 text-right mt-1">0/5000</div>
                
                {{-- File Upload Section --}}
                <div class="mt-3 p-3 border border-dashed border-gray-300 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <label for="file-input" class="flex items-center space-x-2 cursor-pointer text-blue-600 hover:text-blue-800 transition-colors">
                            <i class="fas fa-paperclip"></i>
                            <span class="text-sm">Lampirkan file</span>
                        </label>
                        <input type="file" id="file-input" name="attachment" class="hidden" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <span class="text-xs text-gray-500">Max 10MB • JPG, PNG, PDF, DOC, XLS, TXT</span>
                    </div>
                    <div id="file-preview" class="mt-2 hidden">
                        <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded">
                            <i class="fas fa-file text-gray-500"></i>
                            <span id="file-name" class="text-sm text-gray-700"></span>
                            <button type="button" onclick="clearFileInput()" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between items-center mt-3">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        <span class="text-xs text-gray-600">Posting ke:</span>
                        <select id="community-select" name="group_id" class="text-xs px-2 py-1 border border-gray-300 rounded bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
                            @forelse ($communities as $communityOption)
                                @if ($communityOption->user_permissions['can_post'] ?? false)
                                    <option value="{{ $communityOption->id }}" {{ ($selectedGroup && $selectedGroup->id === $communityOption->id) ? 'selected' : '' }}>
                                        {{ $communityOption->name }}
                                    </option>
                                @endif
                            @empty
                                <option value="">Tidak ada komunitas tersedia</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button id="publish-group-post-button" 
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2"
                                disabled>
                            <span id="publish-button-text">Publikasikan</span>
                            <i id="publish-button-icon" class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>



        @elseif($selectedGroup)
            {{-- ✅ ENHANCED: Clear Permission Denial Message with Submit Content Option --}}
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg p-4 mt-4 text-center">
                <div class="flex items-center justify-center mb-3">
                    <i class="fas fa-lock text-yellow-600 text-2xl mr-2"></i>
                    <h3 class="text-lg font-semibold text-yellow-800">Akses Terbatas</h3>
                </div>
                <p class="text-yellow-700 text-sm mb-4">
                    {{ $selectedGroup->user_permissions['posting_reason'] ?? 'Anda tidak memiliki izin untuk memposting di komunitas ini.' }}
                </p>
                
                {{-- ✅ NEW: Submit Content Button --}}
                @if($currentUser->isRegularUser() && in_array($selectedGroup->name, ['Lomba', 'Lowongan Kerja', 'Seminar', 'Workshop', 'Event & Workshop', 'PKM & Kompetisi']))
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-center mb-2">
                            <i class="fas fa-plus-circle text-blue-600 text-xl mr-2"></i>
                            <h4 class="text-md font-semibold text-blue-800">Ingin Submit Konten?</h4>
                        </div>
                        <p class="text-blue-700 text-sm mb-3">
                            Submit konten Anda (lomba, lowongan, seminar, workshop) dengan mudah! 
                            Konten akan direview oleh moderator setelah pembayaran Rp 5.000.
                        </p>
                        <a href="{{ route('submissions.create', ['category' => $selectedGroup->id]) }}" 
                           class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Submit Konten Anda
                        </a>
                    </div>
                @endif
                
                <div class="text-xs text-yellow-600">
                    <p><strong>Siapa yang bisa posting:</strong></p>
                    <ul class="list-disc list-inside mt-1">
                        <li>👑 Administrator (semua komunitas)</li>
                        <li>🛡️ Moderator yang ditugaskan</li>
                        @if($selectedGroup->moderator)
                            <li>Moderator saat ini: <strong>{{ $selectedGroup->moderator->full_name }}</strong></li>
                        @else
                            <li class="text-orange-600">⚠️ Komunitas ini belum memiliki moderator</li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif



    </div>
</div>

{{-- ✅ NEW: Admin Panel Modal --}}
@if($currentUser->isAdmin())
<div id="admin-panel-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">👑 Panel Administrator</h3>
            <button onclick="closeAdminPanel()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-purple-800 mb-2">🛡️ Kelola Moderator</h4>
                    <p class="text-sm text-purple-600 mb-3">Tugaskan dan kelola moderator untuk setiap komunitas</p>
                    <button onclick="openModeratorManagement()" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded text-sm transition-colors">
                        Kelola Moderator
                    </button>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-2">📊 Statistik Komunitas</h4>
                    <p class="text-sm text-blue-600 mb-3">Lihat statistik dan aktivitas komunitas</p>
                    <button onclick="openCommunityStats()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm transition-colors">
                        Lihat Statistik
                    </button>
                </div>
            </div>
            
            <div class="bg-red-50 p-4 rounded-lg">
                <h4 class="font-semibold text-red-800 mb-2">⚠️ Zona Bahaya</h4>
                <p class="text-sm text-red-600 mb-3">Aksi yang memerlukan konfirmasi ekstra</p>
                <div class="space-x-2">
                    <button onclick="openBulkActions()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm transition-colors">
                        Aksi Massal
                    </button>
                    <button onclick="openSystemSettings()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm transition-colors">
                        Pengaturan Sistem
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Loading Overlay --}}
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-gray-700">Memproses...</p>
    </div>
</div>

{{-- Success/Error Toast Container --}}
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

@push('scripts')
<script>
// ✅ COMPLETE JAVASCRIPT IMPLEMENTATION - WORKING VERSION
document.addEventListener('DOMContentLoaded', function() {
    console.log('Community chat page loaded');

    // DOM elements
    const messageInput = document.getElementById('group-message-input');
    const fileInput = document.getElementById('file-input');
    const publishButton = document.getElementById('publish-group-post-button');
    const charCounter = document.getElementById('char-counter');
    const filePreview = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');
    const communityForm = document.getElementById('community-post-form');
    const loadingOverlay = document.getElementById('loading-overlay');
    
    // Global variables
    let selectedFile = null;
    let isSubmitting = false;

    // ✅ CHARACTER COUNTER for textarea
    if (messageInput && charCounter) {
        messageInput.addEventListener('input', function() {
            const currentLength = this.value.length;
            const maxLength = 5000;
            
            charCounter.textContent = `${currentLength}/${maxLength}`;
            
            // Update counter color based on length
            if (currentLength > maxLength * 0.9) {
                charCounter.classList.add('text-red-500');
                charCounter.classList.remove('text-gray-500');
            } else {
                charCounter.classList.add('text-gray-500');
                charCounter.classList.remove('text-red-500');
            }
            
            // Check if button should be enabled
            updatePublishButtonState();
        });
    }

    // ✅ FILE INPUT HANDLER
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (10MB = 10 * 1024 * 1024 bytes)
                if (file.size > 10 * 1024 * 1024) {
                    showToast('Ukuran file terlalu besar. Maksimal 10MB.', 'error');
                    clearFileInput();
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'text/plain'];
                
                if (!allowedTypes.includes(file.type)) {
                    showToast('Tipe file tidak didukung. Gunakan JPG, PNG, PDF, DOC, XLS, atau TXT.', 'error');
                    clearFileInput();
                    return;
                }
                
                selectedFile = file;
                fileName.textContent = file.name;
                filePreview.classList.remove('hidden');
                
                showToast('File berhasil dipilih: ' + file.name, 'success');
                updatePublishButtonState();
            }
        });
    }

    // ✅ CLEAR FILE INPUT FUNCTION
    window.clearFileInput = function() {
        if (fileInput) {
            fileInput.value = '';
        }
        selectedFile = null;
        if (filePreview) {
            filePreview.classList.add('hidden');
        }
        updatePublishButtonState();
        showToast('File dihapus', 'info');
    };

    // ✅ UPDATE PUBLISH BUTTON STATE
    function updatePublishButtonState() {
        if (!publishButton) return;
        
        const hasContent = messageInput && messageInput.value.trim().length > 0;
        const hasFile = selectedFile !== null;
        const hasValidCommunity = document.getElementById('community-select') && 
                                 document.getElementById('community-select').value !== '';
        
        // Enable button if has content OR file, AND has valid community
        const shouldEnable = (hasContent || hasFile) && hasValidCommunity && !isSubmitting;
        
        publishButton.disabled = !shouldEnable;
        
        if (shouldEnable) {
            publishButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
            publishButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
        } else {
            publishButton.classList.add('bg-gray-400', 'cursor-not-allowed');
            publishButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
        }
        
        console.log('Button state updated:', {
            hasContent,
            hasFile,
            hasValidCommunity,
            shouldEnable,
            isSubmitting
        });
    }

    // ✅ FORM SUBMISSION HANDLER
    if (communityForm) {
        communityForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (isSubmitting) {
                console.log('Already submitting, ignoring duplicate submit');
                return;
            }
            
            const messageContent = messageInput ? messageInput.value.trim() : '';
            const groupId = document.getElementById('community-select') ? 
                           document.getElementById('community-select').value : '';
            
            // Validation
            if (!messageContent && !selectedFile) {
                showToast('Mohon isi pesan atau lampirkan file', 'warning');
                return;
            }
            
            if (!groupId) {
                showToast('Mohon pilih komunitas tujuan', 'warning');
                return;
            }
            
            if (messageContent.length > 5000) {
                showToast('Pesan terlalu panjang (maksimal 5000 karakter)', 'error');
                return;
            }
            
            console.log('Submitting form with:', {
                messageContent: messageContent.substring(0, 100) + '...',
                groupId,
                hasFile: !!selectedFile,
                fileName: selectedFile ? selectedFile.name : null
            });
            
            submitCommunityPost(messageContent, groupId);
        });
    }

    // ✅ SUBMIT COMMUNITY POST FUNCTION
    function submitCommunityPost(messageContent, groupId) {
        isSubmitting = true;
        updatePublishButtonState();
        showLoadingOverlay(true);
        
        // Update button text to show loading
        const buttonText = document.getElementById('publish-button-text');
        const buttonIcon = document.getElementById('publish-button-icon');
        
        if (buttonText) buttonText.textContent = 'Mengirim...';
        if (buttonIcon) {
            buttonIcon.classList.remove('fa-paper-plane');
            buttonIcon.classList.add('fa-spinner', 'fa-spin');
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        formData.append('group_id', groupId);
        formData.append('message_content', messageContent);
        
        if (selectedFile) {
            formData.append('attachment', selectedFile);
        }
        
        // Submit to Laravel
        fetch('/community/send-message', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            
            if (data.status === 'success') {
                showToast(data.message || 'Pesan berhasil dipublikasikan!', 'success');
                
                // Clear form
                if (messageInput) messageInput.value = '';
                clearFileInput();
                updateCharCounter();
                
                // Add message to UI if sent_message data is provided
                if (data.sent_message) {
                    addMessageToUI(data.sent_message);
                } else {
                    // Reload page to show new message
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
                
            } else {
                // Handle error response
                const errorMessage = data.message || 'Terjadi kesalahan saat mengirim pesan';
                showToast(errorMessage, 'error');
                
                if (data.errors) {
                    Object.values(data.errors).forEach(errorArray => {
                        errorArray.forEach(error => {
                            showToast(error, 'error');
                        });
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error submitting post:', error);
            showToast('Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
        })
        .finally(() => {
            // Reset button state
            isSubmitting = false;
            updatePublishButtonState();
            showLoadingOverlay(false);
            
            // Reset button text
            if (buttonText) buttonText.textContent = 'Publikasikan';
            if (buttonIcon) {
                buttonIcon.classList.remove('fa-spinner', 'fa-spin');
                buttonIcon.classList.add('fa-paper-plane');
            }
        });
    }

    // ✅ ADD MESSAGE TO UI (for real-time updates)
    function addMessageToUI(messageData) {
        const messagesList = document.getElementById('messages-list');
        if (!messagesList) return;
        
        // Create message HTML (simplified version)
        const messageHtml = `
            <div class="community-post-card bg-white p-4 rounded-lg shadow-md mb-4 border-l-4 border-blue-400 animate-fadeIn" data-message-id="${messageData.id}">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 rounded-full bg-blue-500 mr-3 flex-shrink-0 overflow-hidden flex items-center justify-center">
                        <span class="text-white font-bold text-sm">${messageData.sender.full_name.charAt(0).toUpperCase()}</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <p class="font-semibold text-gray-800">${messageData.sender.full_name}</p>
                            <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">${messageData.sender.role}</span>
                        </div>
                        <p class="text-xs text-gray-500">${messageData.created_at} • Baru saja</p>
                    </div>
                </div>
                <div class="prose prose-sm max-w-none">
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">${messageData.message_content}</p>
                </div>
                ${messageData.has_attachment ? `
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg border">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-file text-gray-500"></i>
                            <a href="${messageData.attachment_url}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                ${messageData.attachment_name}
                            </a>
                            <span class="text-gray-400 text-xs">${messageData.formatted_file_size}</span>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        
        // Add to top of messages list
        messagesList.insertAdjacentHTML('beforeend', messageHtml);
        
        // Scroll to new message
        const newMessage = messagesList.lastElementChild;
        if (newMessage) {
            newMessage.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // ✅ UPDATE CHARACTER COUNTER
    function updateCharCounter() {
        if (messageInput && charCounter) {
            const currentLength = messageInput.value.length;
            charCounter.textContent = `${currentLength}/5000`;
            
            if (currentLength > 4500) {
                charCounter.classList.add('text-red-500');
                charCounter.classList.remove('text-gray-500');
            } else {
                charCounter.classList.add('text-gray-500');
                charCounter.classList.remove('text-red-500');
            }
        }
    }

    // ✅ LOADING OVERLAY CONTROL
    function showLoadingOverlay(show) {
        if (loadingOverlay) {
            if (show) {
                loadingOverlay.classList.remove('hidden');
            } else {
                loadingOverlay.classList.add('hidden');
            }
        }
    }

    // ✅ COMMUNITY SELECT HANDLER
    const communitySelect = document.getElementById('community-select');
    if (communitySelect) {
        communitySelect.addEventListener('change', function() {
            updatePublishButtonState();
        });
    }

    // ✅ ADMIN PANEL FUNCTIONS
    window.openAdminPanel = function() {
        const modal = document.getElementById('admin-panel-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    };
    
    window.closeAdminPanel = function() {
        const modal = document.getElementById('admin-panel-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    };
    
    window.openModeratorAssignment = function(groupId) {
        console.log('Opening moderator assignment for group:', groupId);
        showToast('Fitur penugasan moderator akan segera tersedia', 'info');
    };
    
    window.openGroupSettings = function(groupId) {
        console.log('Opening group settings for:', groupId);
        showToast('Fitur pengaturan grup akan segera tersedia', 'info');
    };
    
    window.toggleMessageActions = function(messageId) {
        const actionsMenu = document.getElementById(`message-actions-${messageId}`);
        if (actionsMenu) {
            actionsMenu.classList.toggle('hidden');
        }
    };
    
    window.editMessage = function(messageId) {
        console.log('Editing message:', messageId);
        showToast('Fitur edit pesan akan segera tersedia', 'info');
    };
    
    window.deleteMessage = function(messageId) {
        if (confirm('Apakah Anda yakin ingin menghapus pesan ini?')) {
            console.log('Deleting message:', messageId);
            showToast('Fitur hapus pesan akan segera tersedia', 'info');
        }
    };

    // ✅ REACTION FUNCTIONS
    window.toggleReaction = function(messageId, reactionType) {
        console.log('Toggling reaction:', messageId, reactionType);
        
        fetch('/community/reactions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                message_id: messageId,
                reaction_type: reactionType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update reaction counts in UI
                const reactionButton = document.querySelector(`[onclick="toggleReaction(${messageId}, '${reactionType}')"]`);
                const countSpan = reactionButton ? reactionButton.querySelector(`.reaction-count-${reactionType}`) : null;
                
                if (countSpan && data.reaction_counts) {
                    countSpan.textContent = data.reaction_counts[reactionType] || 0;
                }
                
                // Update button appearance based on user's reaction
                if (reactionButton) {
                    if (data.user_reaction === reactionType) {
                        reactionButton.classList.add('text-red-500');
                        reactionButton.classList.remove('text-gray-500');
                    } else {
                        reactionButton.classList.add('text-gray-500');
                        reactionButton.classList.remove('text-red-500');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error toggling reaction:', error);
            showToast('Gagal memberikan reaksi', 'error');
        });
    };

    // ✅ COMMENT FUNCTIONS
    window.toggleCommentBox = function(messageId) {
        const commentSection = document.querySelector(`.comments-section-${messageId}`);
        if (commentSection) {
            commentSection.classList.toggle('hidden');
        }
    };

    window.submitComment = function(messageId) {
        const commentInput = document.querySelector(`.comment-input[data-message-id="${messageId}"]`);
        if (!commentInput) return;
        
        const commentContent = commentInput.value.trim();
        if (!commentContent) {
            showToast('Mohon isi komentar', 'warning');
            return;
        }
        
        fetch('/community/comments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                message_id: messageId,
                comment_content: commentContent
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Clear input
                commentInput.value = '';
                
                // Add comment to UI
                const commentsList = document.querySelector(`.comments-list-${messageId}`);
                if (commentsList && data.comment) {
                    const commentHtml = `
                        <div class="flex space-x-2 p-2 bg-gray-50 rounded-lg">
                            <div class="w-6 h-6 rounded-full bg-blue-500 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                <span class="text-white font-bold text-xs">${data.comment.user.full_name.charAt(0).toUpperCase()}</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold text-sm text-gray-800">${data.comment.user.full_name}</span>
                                    <span class="text-xs text-gray-500">${data.comment.created_at}</span>
                                </div>
                                <p class="text-sm text-gray-700 mt-1">${data.comment.comment_content}</p>
                            </div>
                        </div>
                    `;
                    commentsList.insertAdjacentHTML('beforeend', commentHtml);
                }
                
                // Update comment count
                const commentCountSpan = document.querySelector(`[onclick="toggleCommentBox(${messageId})"] .comment-count`);
                if (commentCountSpan && data.total_comments) {
                    commentCountSpan.textContent = data.total_comments;
                }
                
                showToast('Komentar berhasil ditambahkan', 'success');
            } else {
                showToast(data.message || 'Gagal menambahkan komentar', 'error');
            }
        })
        .catch(error => {
            console.error('Error submitting comment:', error);
            showToast('Gagal menambahkan komentar', 'error');
        });
    };

    // ✅ CLOSE DROPDOWNS WHEN CLICKING OUTSIDE
    document.addEventListener('click', function(event) {
        if (!event.target.closest('[onclick*="toggleMessageActions"]')) {
            document.querySelectorAll('[id^="message-actions-"]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

    // ✅ INITIAL BUTTON STATE UPDATE
    updatePublishButtonState();
    
    console.log('Community chat JavaScript initialized successfully');
});

// ✅ ENHANCED TOAST FUNCTION
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;
    
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

// ✅ ENSURE CSRF TOKEN IS AVAILABLE
document.addEventListener('DOMContentLoaded', function() {
    // Check if CSRF token exists
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.warn('CSRF token not found. Adding it...');
        const metaTag = document.createElement('meta');
        metaTag.setAttribute('name', 'csrf-token');
        metaTag.setAttribute('content', '{{ csrf_token() }}');
        document.head.appendChild(metaTag);
    }
});
</script>

{{-- ✅ Add CSS for animations --}}
<style>
.animate-fadeIn {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
@endpush

@endsection