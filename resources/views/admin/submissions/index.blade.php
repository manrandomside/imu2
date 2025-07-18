@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-7xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-blue-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-purple-500 flex items-center justify-center">
                    <i class="fas fa-tasks text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard - Submissions</h1>
                    <p class="text-gray-600">Kelola dan review konten submission dari user</p>
                </div>
            </div>
            
            {{-- Header Actions --}}
            <div class="flex space-x-2">
                <button onclick="openBulkActions()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-check-double mr-2"></i>
                    Bulk Actions
                </button>
                <button onclick="exportSubmissions()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Export
                </button>
                <a href="{{ route('admin.payments.index') }}" 
                   class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-credit-card mr-2"></i>
                    Kelola Pembayaran
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="p-6 border-b border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg text-center border border-blue-200 hover:shadow-md transition-shadow">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['total'] ?? 0 }}</div>
                <div class="text-sm text-blue-800">Total Submission</div>
                <div class="text-xs text-blue-600 mt-1">+{{ $stats['today_submissions'] ?? 0 }} hari ini</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg text-center border border-yellow-200 hover:shadow-md transition-shadow">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending_approval'] ?? 0 }}</div>
                <div class="text-sm text-yellow-800">Perlu Review</div>
                <div class="text-xs text-yellow-600 mt-1">
                    <i class="fas fa-clock mr-1"></i>Prioritas tinggi
                </div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg text-center border border-green-200 hover:shadow-md transition-shadow">
                <div class="text-2xl font-bold text-green-600">{{ $stats['approved'] ?? 0 }}</div>
                <div class="text-sm text-green-800">Siap Publish</div>
                <div class="text-xs text-green-600 mt-1">
                    <i class="fas fa-rocket mr-1"></i>Ready to go
                </div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg text-center border border-purple-200 hover:shadow-md transition-shadow">
                <div class="text-2xl font-bold text-purple-600">{{ $stats['published'] ?? 0 }}</div>
                <div class="text-sm text-purple-800">Dipublikasi</div>
                <div class="text-xs text-purple-600 mt-1">
                    <i class="fas fa-eye mr-1"></i>Live content
                </div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg text-center border border-red-200 hover:shadow-md transition-shadow">
                <div class="text-2xl font-bold text-red-600">{{ $stats['rejected'] ?? 0 }}</div>
                <div class="text-sm text-red-800">Ditolak</div>
                <div class="text-xs text-red-600 mt-1">
                    <i class="fas fa-times mr-1"></i>Need review
                </div>
            </div>
        </div>
        
        {{-- Revenue Info --}}
        <div class="mt-4 p-4 bg-green-100 rounded-lg border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-green-800">Total Revenue</h3>
                    <p class="text-sm text-green-600">Dari semua submission yang dikonfirmasi</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-green-600">
                        Rp {{ number_format($stats['revenue'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-green-600">
                        +Rp {{ number_format($stats['today_revenue'] ?? 0, 0, ',', '.') }} hari ini
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters & Bulk Selection --}}
    <div class="p-6 border-b border-gray-200 bg-gray-50">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.submissions.index') }}" class="flex flex-wrap items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="pending_approval" {{ ($status ?? '') === 'pending_approval' ? 'selected' : '' }}>Perlu Review</option>
                        <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>Siap Publish</option>
                        <option value="published" {{ ($status ?? '') === 'published' ? 'selected' : '' }}>Dipublikasi</option>
                        <option value="rejected" {{ ($status ?? '') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">Semua Kategori</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat->id }}" {{ ($category ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm transition-colors">
                        <i class="fas fa-search mr-1"></i>
                        Filter
                    </button>
                    <a href="{{ route('admin.submissions.index') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm transition-colors">
                        <i class="fas fa-undo mr-1"></i>
                        Reset
                    </a>
                </div>
            </form>

            {{-- Bulk Selection Controls --}}
            <div id="bulk-controls" class="hidden">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600" id="selected-count">0 selected</span>
                    <button onclick="selectAll()" class="text-sm text-blue-600 hover:text-blue-800">Select All</button>
                    <button onclick="clearSelection()" class="text-sm text-gray-600 hover:text-gray-800">Clear</button>
                    <button onclick="processBulkAction('approve')" class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm">
                        <i class="fas fa-check mr-1"></i>Approve
                    </button>
                    <button onclick="processBulkAction('reject')" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
                        <i class="fas fa-times mr-1"></i>Reject
                    </button>
                    <button onclick="processBulkAction('publish')" class="px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white rounded text-sm">
                        <i class="fas fa-rocket mr-1"></i>Publish
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Submissions List --}}
    <div class="p-6">
        @if(isset($submissions) && $submissions->count() > 0)
            <div class="space-y-4">
                @foreach($submissions as $submission)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow submission-card" 
                         data-id="{{ $submission->id }}" 
                         data-submission-id="{{ $submission->id }}">
                        <div class="flex items-start justify-between">
                            {{-- Checkbox for bulk selection --}}
                            <div class="flex items-start space-x-3 flex-1">
                                <div class="pt-1">
                                    <input type="checkbox" class="submission-checkbox rounded" value="{{ $submission->id }}" onchange="updateBulkControls()">
                                </div>
                                
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="text-2xl">{{ $submission->category->icon ?? '📋' }}</div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-lg text-gray-800">{{ $submission->title }}</h3>
                                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                <span>
                                                    <i class="fas fa-user mr-1"></i>
                                                    {{ $submission->user->full_name }}
                                                </span>
                                                <span>
                                                    <i class="fas fa-folder mr-1"></i>
                                                    {{ $submission->category->name }}
                                                </span>
                                                <span>
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    {{ $submission->created_at->format('d M Y, H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Status badges and progress --}}
                                    <div class="flex items-center space-x-2 mb-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $submission->status_badge_color ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $submission->status_icon ?? '⏳' }} 
                                            @switch($submission->status)
                                                @case('pending_payment')
                                                    Menunggu Pembayaran
                                                    @break
                                                @case('pending_approval')
                                                    Perlu Review
                                                    @break
                                                @case('approved')
                                                    Siap Publish
                                                    @break
                                                @case('published')
                                                    Dipublikasi
                                                    @break
                                                @case('rejected')
                                                    Ditolak
                                                    @break
                                                @default
                                                    {{ ucfirst($submission->status) }}
                                            @endswitch
                                        </span>
                                        
                                        @if($submission->payment)
                                            <span class="px-2 py-1 rounded-full text-xs {{ $submission->payment->status === 'confirmed' ? 'bg-green-100 text-green-800' : ($submission->payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                💰 {{ $submission->payment->status === 'confirmed' ? 'Dikonfirmasi' : ($submission->payment->status === 'pending' ? 'Pending' : 'Ditolak') }}
                                            </span>
                                        @endif
                                        
                                        @if($submission->attachment_path)
                                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
                                                <i class="fas fa-paperclip"></i>
                                                File
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="mb-3">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs text-gray-600">Progress</span>
                                            <span class="text-xs text-gray-600">
                                                @php
                                                    $progress = match($submission->status) {
                                                        'pending_payment' => 25,
                                                        'pending_approval' => 50,
                                                        'approved' => 75,
                                                        'published' => 100,
                                                        'rejected' => 0,
                                                        default => 0
                                                    };
                                                @endphp
                                                {{ $progress }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            @php
                                                $progressColor = match($submission->status) {
                                                    'pending_payment' => 'bg-yellow-500',
                                                    'pending_approval' => 'bg-blue-500',
                                                    'approved' => 'bg-green-500',
                                                    'published' => 'bg-purple-500',
                                                    'rejected' => 'bg-red-500',
                                                    default => 'bg-gray-500'
                                                };
                                            @endphp
                                            <div class="h-2 rounded-full {{ $progressColor }} transition-all duration-300" style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-700 text-sm line-clamp-2 mb-3">
                                        {{ Str::limit($submission->description, 200) }}
                                    </p>
                                    
                                    @if($submission->status === 'rejected' && $submission->rejection_reason)
                                        <div class="p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700 mb-2">
                                            <strong>Ditolak:</strong> {{ $submission->rejection_reason }}
                                        </div>
                                    @endif

                                    {{-- Additional info for published content --}}
                                    @if($submission->status === 'published')
                                        <div class="p-2 bg-purple-50 border border-purple-200 rounded text-sm text-purple-700 mb-2">
                                            <i class="fas fa-rocket mr-1"></i>
                                            <strong>Dipublikasi:</strong> {{ $submission->published_at ? $submission->published_at->diffForHumans() : 'Recently' }}
                                            @if($submission->publishedBy)
                                                oleh {{ $submission->publishedBy->full_name }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Enhanced Action Buttons --}}
                            <div class="flex flex-col space-y-2 ml-4 min-w-max">
                                {{-- Always available actions --}}
                                <a href="{{ route('submissions.show', $submission) }}" 
                                   class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors text-center">
                                    <i class="fas fa-eye mr-1"></i>
                                    Detail
                                </a>

                                {{-- Admin Edit Button (always available for admin) --}}
                                <button onclick="editSubmission({{ $submission->id }}, '{{ addslashes($submission->title) }}')" 
                                        class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm transition-colors">
                                    <i class="fas fa-edit mr-1"></i>
                                    Edit
                                </button>
                                
                                {{-- Status-specific actions --}}
                                @if($submission->status === 'pending_approval' && $submission->payment && $submission->payment->status === 'confirmed')
                                    <button onclick="approveSubmission({{ $submission->id }}, '{{ addslashes($submission->title) }}')" 
                                            class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-check mr-1"></i>
                                        Approve
                                    </button>
                                    
                                    <button onclick="openRejectionModal({{ $submission->id }}, '{{ addslashes($submission->title) }}')" 
                                            class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-times mr-1"></i>
                                        Tolak
                                    </button>
                                @endif
                                
                                @if($submission->status === 'approved')
                                    <button onclick="publishSubmission({{ $submission->id }}, '{{ addslashes($submission->title) }}')" 
                                            class="px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-rocket mr-1"></i>
                                        Publish
                                    </button>
                                @endif

                                @if($submission->status === 'published')
                                    <button onclick="republishSubmission({{ $submission->id }}, '{{ addslashes($submission->title) }}')" 
                                            class="px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-redo mr-1"></i>
                                        Re-publish
                                    </button>
                                @endif

                                {{-- Reject/Re-approve for specific statuses --}}
                                @if(in_array($submission->status, ['pending_approval', 'approved']))
                                    <button onclick="openRejectionModal({{ $submission->id }}, '{{ addslashes($submission->title) }}')" 
                                            class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-times mr-1"></i>
                                        Tolak
                                    </button>
                                @endif

                                @if($submission->status === 'rejected' && $submission->payment && $submission->payment->status === 'confirmed')
                                    <button onclick="reapproveSubmission({{ $submission->id }}, '{{ addslashes($submission->title) }}')" 
                                            class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-redo mr-1"></i>
                                        Re-approve
                                    </button>
                                @endif

                                {{-- ✅ DEBUG BUTTON (only for development) --}}
                                @if(app()->environment('local'))
                                    <button onclick="debugSubmission({{ $submission->id }})" 
                                            class="px-2 py-1 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded text-xs transition-colors">
                                        <i class="fas fa-bug mr-1"></i>
                                        Debug
                                    </button>
                                @endif

                                {{-- ✅ TEST BUTTON untuk testing publish --}}
                                @if(app()->environment('local') && $submission->status === 'approved')
                                    <button onclick="testPublish({{ $submission->id }}, '{{ addslashes($submission->title) }}')" 
                                            class="px-2 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded text-xs transition-colors">
                                        <i class="fas fa-flask mr-1"></i>
                                        Test
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Pagination --}}
            @if($submissions->hasPages())
                <div class="mt-6">
                    {{ $submissions->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            {{-- Enhanced Empty State --}}
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Tidak ada submission</h3>
                <p class="text-gray-500 mb-4">Belum ada submission dengan filter yang dipilih.</p>
                <a href="{{ route('admin.submissions.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm transition-colors">
                    <i class="fas fa-refresh mr-2"></i>
                    Reset Filter
                </a>
            </div>
        @endif
    </div>
</div>

{{-- Approval Modal --}}
<div id="approval-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Setujui Submission</h3>
        <p class="text-gray-600 mb-4">Apakah Anda yakin ingin menyetujui submission "<span id="approval-title"></span>"?</p>
        
        <form id="approval-form" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="publish" value="1" class="mr-2">
                    <span class="text-sm text-gray-700">Langsung publikasikan ke komunitas</span>
                </label>
            </div>
            
            <div class="flex items-center justify-end space-x-3">
                <button type="button" onclick="closeApprovalModal()" 
                        class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded">
                    Setujui
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Rejection Modal --}}
<div id="rejection-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Tolak Submission</h3>
        <p class="text-gray-600 mb-4">Berikan alasan penolakan untuk "<span id="rejection-title"></span>":</p>
        
        <form id="rejection-form" method="POST" action="">
            @csrf
            <div class="mb-4">
                <textarea name="rejection_reason" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400"
                          rows="4" 
                          placeholder="Masukkan alasan penolakan..." 
                          required></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-3">
                <button type="button" onclick="closeRejectionModal()" 
                        class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded">
                    Tolak
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Action Modal --}}
<div id="bulk-action-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Bulk Action</h3>
        <p class="text-gray-600 mb-4">Pilih aksi untuk <span id="bulk-count">0</span> submission yang dipilih:</p>
        
        <div class="space-y-2 mb-4">
            <button onclick="executeBulkAction('approve')" class="w-full px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded">
                <i class="fas fa-check mr-2"></i>Approve Selected
            </button>
            <button onclick="executeBulkAction('publish')" class="w-full px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded">
                <i class="fas fa-rocket mr-2"></i>Publish Selected
            </button>
            <button onclick="showBulkRejectForm()" class="w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded">
                <i class="fas fa-times mr-2"></i>Reject Selected
            </button>
        </div>
        
        <div id="bulk-reject-form" class="hidden mb-4">
            <textarea id="bulk-rejection-reason" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                      rows="3" 
                      placeholder="Masukkan alasan penolakan untuk semua submission..."></textarea>
        </div>
        
        <div class="flex items-center justify-end space-x-3">
            <button type="button" onclick="closeBulkActionModal()" 
                    class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                Batal
            </button>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
        <p class="text-gray-700">Memproses...</p>
    </div>
</div>

@push('scripts')
<script>
// ✅ FIXED JAVASCRIPT FOR ADMIN SUBMISSIONS WITH COMPREHENSIVE DEBUGGING

// Enhanced Modal functions
function openApprovalModal(submissionId, title) {
    document.getElementById('approval-title').textContent = title;
    document.getElementById('approval-form').action = `/admin/submissions/${submissionId}/approve`;
    document.getElementById('approval-modal').classList.remove('hidden');
}

function closeApprovalModal() {
    document.getElementById('approval-modal').classList.add('hidden');
}

function openRejectionModal(submissionId, title) {
    document.getElementById('rejection-title').textContent = title;
    document.getElementById('rejection-form').action = `/admin/submissions/${submissionId}/reject`;
    document.getElementById('rejection-modal').classList.remove('hidden');
}

function closeRejectionModal() {
    document.getElementById('rejection-modal').classList.add('hidden');
}

// ✅ ENHANCED: approveSubmission with better debugging
function approveSubmission(id, title) {
    console.log('🟡 [INDIVIDUAL] Attempting to approve submission:', { id, title });
    
    if (confirm(`Setujui submission "${title}"?`)) {
        showLoading();
        
        const approveUrl = `/admin/submissions/${id}/approve`;
        console.log('🔗 [INDIVIDUAL] Approve URL:', approveUrl);
        
        fetch(approveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('📥 [INDIVIDUAL] Approve response status:', response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('❌ [INDIVIDUAL] Approve response text:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            console.log('✅ [INDIVIDUAL] Approve response data:', data);
            
            if (data.success) {
                showToast('Submission berhasil disetujui!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Terjadi kesalahan saat menyetujui submission', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('❌ [INDIVIDUAL] Error approving submission:', error);
            showToast('Terjadi kesalahan saat menyetujui submission: ' + error.message, 'error');
        });
    }
}

// ✅ FIXED INDIVIDUAL PUBLISH FUNCTION WITH COMPREHENSIVE DEBUGGING
function publishSubmission(id, title) {
    console.log('🚀 [INDIVIDUAL] Publishing submission:', { 
        id: id, 
        title: title, 
        type: 'INDIVIDUAL_PUBLISH',
        timestamp: new Date().toISOString() 
    });
    
    // ✅ CRITICAL: Detect if accidentally called with bulk parameters
    if (typeof id === 'string' && id === 'publish') {
        console.error('❌ ERROR: Individual publish called with bulk action parameter!');
        console.error('❌ This should NOT happen. Check the onclick attribute.');
        showToast('Error: Individual publish dipanggil dengan parameter bulk action!', 'error');
        return;
    }
    
    // ✅ VALIDATION: Ensure parameters are correct
    if (!id || !title) {
        console.error('❌ ERROR: Missing required parameters', { id, title });
        showToast('Error: Parameter ID atau title tidak lengkap!', 'error');
        return;
    }
    
    // ✅ ENHANCED CONFIRMATION with clear individual publish marker
    const confirmMessage = `🚀 INDIVIDUAL PUBLISH\n\nSubmission ID: ${id}\nTitle: ${title}\n\nPublikasikan ke komunitas?`;
    
    if (confirm(confirmMessage)) {
        showLoading();
        
        // ✅ EXPLICIT INDIVIDUAL PUBLISH URL
        const publishUrl = `/admin/submissions/${id}/publish`;
        console.log('🔗 [INDIVIDUAL] Publish URL:', publishUrl);
        console.log('🔑 [INDIVIDUAL] CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 'NOT FOUND');
        
        // ✅ Enhanced debugging - check submission card data
        const submissionCard = document.querySelector(`[data-submission-id="${id}"]`);
        if (submissionCard) {
            console.log('📋 [INDIVIDUAL] Submission card found:', submissionCard);
            const statusBadge = submissionCard.querySelector('.rounded-full');
            console.log('🏷️ [INDIVIDUAL] Current status badge:', statusBadge?.textContent?.trim());
        } else {
            console.warn('⚠️ [INDIVIDUAL] Submission card not found for ID:', id);
        }
        
        // ✅ EXPLICIT HEADERS for individual publish
        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Individual-Publish': 'true' // ✅ MARKER for individual publish
        };
        
        console.log('📤 [INDIVIDUAL] Request headers:', headers);
        
        fetch(publishUrl, {
            method: 'POST',
            headers: headers
        })
        .then(response => {
            console.log('📥 [INDIVIDUAL] Response status:', response.status);
            console.log('📥 [INDIVIDUAL] Response ok:', response.ok);
            console.log('📥 [INDIVIDUAL] Response headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('❌ [INDIVIDUAL] Response text:', text);
                    console.error('❌ [INDIVIDUAL] Response details:', {
                        status: response.status,
                        statusText: response.statusText,
                        url: response.url
                    });
                    throw new Error(`[INDIVIDUAL] HTTP ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            console.log('✅ [INDIVIDUAL] Response data:', data);
            
            // ✅ CHECK: Ensure this is individual response, not bulk
            if (data.message && data.message.includes('submission berhasil diproses')) {
                console.error('❌ ERROR: Received bulk action response for individual publish!');
                console.error('❌ This indicates the wrong endpoint was called.');
                showToast('Error: Individual publish mendapat response bulk action!', 'error');
                return;
            }
            
            if (data.success) {
                console.log('✅ [INDIVIDUAL] Publish successful!');
                showToast('✅ Submission berhasil dipublikasikan ke komunitas! (Individual)', 'success');
                
                setTimeout(() => {
                    console.log('🔄 [INDIVIDUAL] Reloading page...');
                    location.reload();
                }, 2000);
            } else {
                console.error('❌ [INDIVIDUAL] Publish failed:', data.message);
                showToast('❌ ' + (data.message || 'Gagal mempublikasikan submission'), 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('❌ [INDIVIDUAL] Publish error:', error);
            console.error('❌ [INDIVIDUAL] Error stack:', error.stack);
            
            showToast('❌ Terjadi kesalahan saat mempublikasikan submission: ' + error.message, 'error');
            
            // ✅ SPECIFIC ERROR DIAGNOSIS
            if (error.message.includes('404')) {
                console.error('🔍 [INDIVIDUAL] DIAGNOSIS: Route not found');
                console.error('🔍 [INDIVIDUAL] Check: routes/web.php for publish route');
            } else if (error.message.includes('403')) {
                console.error('🔍 [INDIVIDUAL] DIAGNOSIS: Permission denied');
                console.error('🔍 [INDIVIDUAL] Check: User has moderator privileges');
            } else if (error.message.includes('419')) {
                console.error('🔍 [INDIVIDUAL] DIAGNOSIS: CSRF token mismatch');
                console.error('🔍 [INDIVIDUAL] Check: CSRF token is valid');
            } else if (error.message.includes('500')) {
                console.error('🔍 [INDIVIDUAL] DIAGNOSIS: Server error');
                console.error('🔍 [INDIVIDUAL] Check: Laravel logs for details');
            }
        });
    } else {
        console.log('❌ [INDIVIDUAL] Publish cancelled by user');
    }
}

// ✅ NEW: Test publish function for troubleshooting
function testPublish(id, title) {
    console.log('🧪 [TEST] Testing publish for submission:', { id, title });
    
    if (confirm(`🧪 TEST PUBLISH\n\nSubmission ID: ${id}\nTitle: ${title}\n\nTest publikasi (tidak akan benar-benar publish)?`)) {
        showLoading();
        
        // Test dengan endpoint yang sama tapi dengan header khusus
        fetch(`/admin/submissions/${id}/publish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Test-Mode': 'true'
            }
        })
        .then(response => {
            console.log('🧪 [TEST] Response status:', response.status);
            return response.text(); // Get as text first to see raw response
        })
        .then(text => {
            hideLoading();
            console.log('🧪 [TEST] Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('🧪 [TEST] Parsed response:', data);
                showToast('🧪 Test completed - check console for details', 'info');
            } catch (e) {
                console.log('🧪 [TEST] Response is not JSON:', text);
                showToast('🧪 Test completed - response is not JSON', 'warning');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('🧪 [TEST] Error:', error);
            showToast('🧪 Test error: ' + error.message, 'error');
        });
    }
}

// ✅ NEW: Debug function to check submission status
function debugSubmission(id) {
    console.log('🐛 [DEBUG] Debug submission:', id);
    
    // Show basic info about the submission from DOM
    const submissionCard = document.querySelector(`[data-submission-id="${id}"]`);
    if (submissionCard) {
        const statusBadge = submissionCard.querySelector('.rounded-full');
        const paymentBadge = submissionCard.querySelectorAll('.rounded-full')[1];
        
        console.log('🐛 [DEBUG] DOM info:', {
            submissionId: id,
            statusBadge: statusBadge?.textContent?.trim(),
            paymentBadge: paymentBadge?.textContent?.trim(),
            cardElement: submissionCard
        });
    }
    
    // Try to fetch debug info if debug route exists
    fetch(`/debug/submission/${id}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            throw new Error('Debug route not available');
        }
    })
    .then(data => {
        console.log('🐛 [DEBUG] Server debug data:', data);
        
        const debugInfo = `
            <div class="text-left space-y-4">
                <h3 class="text-lg font-bold">🐛 Debug Info for Submission ${id}</h3>
                
                <div>
                    <strong>📋 Submission:</strong>
                    <ul class="ml-4 text-sm">
                        <li>• Status: <code>${data.submission?.status || 'unknown'}</code></li>
                        <li>• Title: ${data.submission?.title || 'unknown'}</li>
                        <li>• User: ${data.submission?.user || 'unknown'}</li>
                    </ul>
                </div>
                
                <div>
                    <strong>💰 Payment:</strong>
                    <ul class="ml-4 text-sm">
                        ${data.payment ? `
                            <li>• Status: <code>${data.payment.status}</code></li>
                            <li>• Amount: Rp ${data.payment.amount}</li>
                        ` : '<li>• No payment found</li>'}
                    </ul>
                </div>
                
                <div>
                    <strong>🔧 Actions:</strong>
                    <ul class="ml-4 text-sm">
                        <li>• Can be published: ${data.can_actions?.can_be_published ? '✅' : '❌'}</li>
                    </ul>
                </div>
            </div>
        `;
        
        showModalWithContent('Debug Information', debugInfo);
    })
    .catch(error => {
        console.log('🐛 [DEBUG] No server debug available:', error.message);
        showToast('🐛 Debug: Check console for DOM info', 'info');
    });
}

function republishSubmission(id, title) {
    console.log('🔄 [INDIVIDUAL] Attempting to republish submission:', { id, title });
    
    if (confirm(`Re-publikasikan submission "${title}" ke komunitas?`)) {
        showLoading();
        
        fetch(`/admin/submissions/${id}/republish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('📥 [INDIVIDUAL] Republish response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            console.log('✅ [INDIVIDUAL] Republish response data:', data);
            if (data.success) {
                showToast('Submission berhasil di-republish!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('❌ [INDIVIDUAL] Error republishing submission:', error);
            showToast('Terjadi kesalahan saat republish submission: ' + error.message, 'error');
        });
    }
}

function editSubmission(id, title) {
    console.log('✏️ [INDIVIDUAL] Opening edit for submission:', { id, title });
    window.open(`/admin/submissions/${id}/edit`, '_blank');
}

function reapproveSubmission(id, title) {
    console.log('🔄 [INDIVIDUAL] Attempting to re-approve submission:', { id, title });
    
    if (confirm(`Re-approve submission "${title}"?`)) {
        showLoading();
        fetch(`/admin/submissions/${id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Submission berhasil di-approve kembali!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error re-approving submission:', error);
            showToast('Terjadi kesalahan saat re-approve submission: ' + error.message, 'error');
        });
    }
}

// ✅ ENHANCED: Bulk action dengan explicit logging
function processBulkAction(action) {
    console.log('📦 [BULK] Processing bulk action:', action);
    console.log('📦 [BULK] This is BULK ACTION, not individual publish');
    
    const checkboxes = document.querySelectorAll('.submission-checkbox:checked');
    const submissionIds = Array.from(checkboxes).map(cb => cb.value);
    
    console.log('📦 [BULK] Selected submissions:', submissionIds);
    
    if (submissionIds.length === 0) {
        showToast('Pilih minimal satu submission untuk bulk ' + action, 'warning');
        return;
    }

    let confirmMessage = `📦 BULK ACTION\n\nAction: ${action.toUpperCase()}\nSubmissions: ${submissionIds.length}\n\nLanjutkan?`;
    
    if (confirm(confirmMessage)) {
        showLoading();
        
        const requestBody = {
            action: action,
            submission_ids: submissionIds
        };

        console.log('📦 [BULK] Request body:', requestBody);

        fetch('/admin/submissions/bulk-action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Bulk-Action': 'true' // ✅ MARKER for bulk action
            },
            body: JSON.stringify(requestBody)
        })
        .then(response => {
            console.log('📦 [BULK] Response status:', response.status);
            if (!response.ok) {
                throw new Error(`[BULK] HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            console.log('📦 [BULK] Response data:', data);
            if (data.success) {
                showToast('📦 ' + (data.message || `Bulk ${action} berhasil!`), 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('❌ ' + (data.message || `Gagal melakukan bulk ${action}`), 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('❌ [BULK] Error:', error);
            showToast(`❌ Terjadi kesalahan saat melakukan bulk ${action}: ` + error.message, 'error');
        });
    }
}

// Bulk selection functions
function updateBulkControls() {
    const checkboxes = document.querySelectorAll('.submission-checkbox:checked');
    const bulkControls = document.getElementById('bulk-controls');
    const selectedCount = document.getElementById('selected-count');
    
    if (checkboxes.length > 0) {
        bulkControls.classList.remove('hidden');
        selectedCount.textContent = `${checkboxes.length} selected`;
    } else {
        bulkControls.classList.add('hidden');
    }
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.submission-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    updateBulkControls();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.submission-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateBulkControls();
}

function openBulkActions() {
    const checkboxes = document.querySelectorAll('.submission-checkbox:checked');
    if (checkboxes.length === 0) {
        showToast('Pilih minimal satu submission untuk bulk action', 'warning');
        return;
    }
    
    document.getElementById('bulk-count').textContent = checkboxes.length;
    document.getElementById('bulk-action-modal').classList.remove('hidden');
}

function closeBulkActionModal() {
    document.getElementById('bulk-action-modal').classList.add('hidden');
    document.getElementById('bulk-reject-form').classList.add('hidden');
}

function showBulkRejectForm() {
    document.getElementById('bulk-reject-form').classList.remove('hidden');
}

function executeBulkAction(action) {
    const checkboxes = document.querySelectorAll('.submission-checkbox:checked');
    const submissionIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (submissionIds.length === 0) {
        showToast('Tidak ada submission yang dipilih', 'warning');
        return;
    }

    let confirmMessage = `${action.charAt(0).toUpperCase() + action.slice(1)} ${submissionIds.length} submission?`;
    let rejectionReason = null;
    
    if (action === 'reject') {
        rejectionReason = document.getElementById('bulk-rejection-reason').value.trim();
        if (!rejectionReason) {
            showToast('Alasan penolakan wajib diisi', 'warning');
            return;
        }
    }
    
    if (confirm(confirmMessage)) {
        showLoading();
        closeBulkActionModal();
        
        const requestBody = {
            action: action,
            submission_ids: submissionIds
        };
        
        if (rejectionReason) {
            requestBody.rejection_reason = rejectionReason;
        }

        fetch('/admin/submissions/bulk-action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestBody)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error in bulk action:', error);
            showToast('Terjadi kesalahan saat memproses bulk action: ' + error.message, 'error');
        });
    }
}

function exportSubmissions() {
    console.log('📊 [INDIVIDUAL] Exporting submissions...');
    showLoading();
    
    const params = new URLSearchParams(window.location.search);
    const exportUrl = '/admin/submissions/export?' + params.toString();
    
    console.log('📊 [INDIVIDUAL] Export URL:', exportUrl);
    
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = 'submissions_export.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    hideLoading();
    showToast('Export dimulai, file akan didownload otomatis', 'success');
}

// ✅ ENHANCED: Utility functions
function showLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
        console.log('⏳ Loading overlay shown');
    } else {
        console.warn('⚠️ Loading overlay element not found');
    }
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('hidden');
        console.log('✅ Loading overlay hidden');
    }
}

function showToast(message, type = 'info', duration = 5000) {
    console.log(`🍞 Toast: [${type.toUpperCase()}] ${message}`);
    
    const existingToast = document.getElementById('dynamic-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.id = 'dynamic-toast';
    
    const bgColor = type === 'success' ? 'bg-green-500' : 
                   type === 'error' ? 'bg-red-500' : 
                   type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    
    const icon = type === 'success' ? '✅' : 
                type === 'error' ? '❌' : 
                type === 'warning' ? '⚠️' : 'ℹ️';
    
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 text-white ${bgColor} transform transition-all duration-300 translate-x-full max-w-md`;
    toast.innerHTML = `
        <div class="flex items-start space-x-2">
            <span class="text-lg">${icon}</span>
            <div class="flex-1">
                <div class="text-sm font-medium">${message}</div>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200 text-lg leading-none">&times;</button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, duration);
}

// ✅ NEW: Show modal with custom content
function showModalWithContent(title, content) {
    const existingModal = document.getElementById('custom-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'custom-modal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-bold text-gray-800">${title}</h3>
                <button onclick="this.closest('#custom-modal').remove()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <div class="text-gray-700">
                ${content}
            </div>
            <div class="mt-6 text-right">
                <button onclick="this.closest('#custom-modal').remove()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded">Close</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.remove();
        }
    });
}

// ✅ Enhanced page load event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Admin submissions page loaded');
    console.log('🔍 Environment:', '{{ app()->environment() }}');
    console.log('🔑 CSRF Token present:', !!document.querySelector('meta[name="csrf-token"]'));
    
    // Close modals when clicking outside
    const modals = ['approval-modal', 'rejection-modal', 'bulk-action-modal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    if (modalId === 'approval-modal') closeApprovalModal();
                    if (modalId === 'rejection-modal') closeRejectionModal();
                    if (modalId === 'bulk-action-modal') closeBulkActionModal();
                }
            });
        }
    });
    
    updateBulkControls();
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeApprovalModal();
            closeRejectionModal();
            closeBulkActionModal();
            
            const customModal = document.getElementById('custom-modal');
            if (customModal) {
                customModal.remove();
            }
        }
    });
    
    // ✅ DIAGNOSTIC: Check publish button onclick attributes
    console.log('🔍 [DIAGNOSTIC] Checking publish button onclick attributes...');
    
    const publishButtons = document.querySelectorAll('[onclick*="publish"]');
    console.log('🔍 [DIAGNOSTIC] Found buttons with publish onclick:', publishButtons.length);
    
    publishButtons.forEach((btn, index) => {
        const onclick = btn.getAttribute('onclick');
        console.log(`🔍 [DIAGNOSTIC] Button ${index + 1}:`, onclick);
        
        if (onclick.includes('publishSubmission(')) {
            console.log('✅ [DIAGNOSTIC] Individual publish button found');
        } else if (onclick.includes('processBulkAction')) {
            console.log('📦 [DIAGNOSTIC] Bulk action button found');
        } else {
            console.warn('⚠️ [DIAGNOSTIC] Unknown publish button type');
        }
    });
    
    const submissionCards = document.querySelectorAll('.submission-card');
    console.log(`📋 Found ${submissionCards.length} submission cards`);
    
    console.log('✅ Admin submissions JavaScript loaded successfully');
});

// ✅ ENHANCED: Global error handler for better debugging
window.addEventListener('error', function(e) {
    console.error('🚨 Global JavaScript Error:', {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        error: e.error
    });
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('🚨 Unhandled Promise Rejection:', e.reason);
});
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.submission-card {
    transition: all 0.3s ease;
}

.submission-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.submission-checkbox {
    transform: scale(1.2);
}

.min-w-max {
    min-width: max-content;
}

/* Enhanced progress bar animation */
.submission-card .rounded-full {
    transition: width 0.5s ease-in-out;
}

/* Button hover effects */
button:hover, .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .submission-card .flex-col {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .submission-card .flex-col > * {
        flex: 1;
        min-width: calc(50% - 0.25rem);
    }
}

/* Debug button styles */
button[onclick^="debugSubmission"] {
    background: linear-gradient(45deg, #6b7280, #9ca3af);
    border: 1px solid #d1d5db;
    font-family: 'Courier New', monospace;
}

button[onclick^="debugSubmission"]:hover {
    background: linear-gradient(45deg, #4b5563, #6b7280);
}

/* Test button styles */
button[onclick^="testPublish"] {
    background: linear-gradient(45deg, #f97316, #ea580c);
    border: 1px solid #fed7aa;
}

button[onclick^="testPublish"]:hover {
    background: linear-gradient(45deg, #ea580c, #dc2626);
}
</style>
@endpush

@endsection