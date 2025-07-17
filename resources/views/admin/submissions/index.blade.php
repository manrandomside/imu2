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
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow submission-card" data-id="{{ $submission->id }}">
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

// New enhanced functions
function approveSubmission(id, title) {
    if (confirm(`Setujui submission "${title}"?`)) {
        showLoading();
        fetch(`/admin/submissions/${id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Submission berhasil disetujui!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('Terjadi kesalahan saat menyetujui submission', 'error');
        });
    }
}

function publishSubmission(id, title) {
    if (confirm(`Publikasikan submission "${title}" ke komunitas?`)) {
        showLoading();
        fetch(`/admin/submissions/${id}/publish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Submission berhasil dipublikasikan!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('Terjadi kesalahan saat mempublikasikan submission', 'error');
        });
    }
}

function republishSubmission(id, title) {
    if (confirm(`Re-publikasikan submission "${title}" ke komunitas?`)) {
        showLoading();
        fetch(`/admin/submissions/${id}/republish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Submission berhasil di-republish!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('Terjadi kesalahan saat republish submission', 'error');
        });
    }
}

function editSubmission(id, title) {
    // Open edit page in new tab or redirect
    window.open(`/admin/submissions/${id}/edit`, '_blank');
}

function reapproveSubmission(id, title) {
    if (confirm(`Re-approve submission "${title}"?`)) {
        showLoading();
        fetch(`/admin/submissions/${id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Submission berhasil di-approve kembali!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('Terjadi kesalahan saat re-approve submission', 'error');
        });
    }
}

// Bulk actions
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(requestBody)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Terjadi kesalahan', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('Terjadi kesalahan saat memproses bulk action', 'error');
        });
    }
}

function exportSubmissions() {
    showLoading();
    
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);
    const exportUrl = '/admin/submissions/export?' + params.toString();
    
    // Create temporary link and click it
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = 'submissions_export.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    hideLoading();
    showToast('Export dimulai, file akan didownload otomatis', 'success');
}

// Utility functions
function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-overlay').classList.add('hidden');
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 
                   type === 'error' ? 'bg-red-500' : 
                   type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 text-white ${bgColor}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Close modals when clicking outside
document.getElementById('approval-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeApprovalModal();
    }
});

document.getElementById('rejection-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectionModal();
    }
});

document.getElementById('bulk-action-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkActionModal();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateBulkControls();
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
</style>
@endpush

@endsection