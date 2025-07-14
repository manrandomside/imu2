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
            <a href="{{ route('admin.payments.index') }}" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-credit-card mr-2"></i>
                Kelola Pembayaran
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="p-6 border-b border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</div>
                <div class="text-sm text-blue-800">Total Submission</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending_approval'] }}</div>
                <div class="text-sm text-yellow-800">Menunggu Review</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</div>
                <div class="text-sm text-green-800">Disetujui</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-purple-600">{{ $stats['published'] }}</div>
                <div class="text-sm text-purple-800">Dipublikasi</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-red-600">{{ $stats['rejected'] }}</div>
                <div class="text-sm text-red-800">Ditolak</div>
            </div>
        </div>
        
        {{-- Revenue Info --}}
        <div class="mt-4 p-4 bg-green-100 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-green-800">Total Revenue</h3>
                    <p class="text-sm text-green-600">Dari semua submission yang dikonfirmasi</p>
                </div>
                <div class="text-2xl font-bold text-green-600">
                    Rp {{ number_format($stats['revenue'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="p-6 border-b border-gray-200 bg-gray-50">
        <form method="GET" action="{{ route('admin.submissions.index') }}" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending_approval" {{ $status === 'pending_approval' ? 'selected' : '' }}>Menunggu Review</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Dipublikasi</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $category == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm transition-colors">
                    <i class="fas fa-search mr-1"></i>
                    Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Submissions List --}}
    <div class="p-6">
        @if($submissions->count() > 0)
            <div class="space-y-4">
                @foreach($submissions as $submission)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <div class="text-2xl">{{ $submission->category->icon }}</div>
                                    <div>
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
                                
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $submission->status_badge_color }}">
                                        {{ $submission->status_icon }} {{ $submission->status_display }}
                                    </span>
                                    
                                    @if($submission->payment)
                                        <span class="px-2 py-1 rounded-full text-xs {{ $submission->payment->status_badge_color }}">
                                            💰 {{ $submission->payment->status_display }}
                                        </span>
                                    @endif
                                    
                                    @if($submission->hasAttachment())
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
                                            <i class="{{ $submission->attachment_icon }}"></i>
                                            File
                                        </span>
                                    @endif
                                </div>
                                
                                <p class="text-gray-700 text-sm line-clamp-2 mb-3">
                                    {{ Str::limit($submission->description, 200) }}
                                </p>
                                
                                @if($submission->status === 'rejected' && $submission->rejection_reason)
                                    <div class="p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700 mb-2">
                                        <strong>Ditolak:</strong> {{ $submission->rejection_reason }}
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="flex flex-col space-y-2 ml-4">
                                <a href="{{ route('submissions.show', $submission) }}" 
                                   class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors">
                                    <i class="fas fa-eye mr-1"></i>
                                    Detail
                                </a>
                                
                                @if($submission->canBeApproved())
                                    <button onclick="openApprovalModal({{ $submission->id }}, '{{ $submission->title }}')" 
                                            class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-check mr-1"></i>
                                        Setujui
                                    </button>
                                    
                                    <button onclick="openRejectionModal({{ $submission->id }}, '{{ $submission->title }}')" 
                                            class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-times mr-1"></i>
                                        Tolak
                                    </button>
                                @endif
                                
                                @if($submission->canBePublished())
                                    <form method="POST" action="{{ route('admin.submissions.publish', $submission) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white rounded text-sm transition-colors"
                                                onclick="return confirm('Publikasikan konten ini ke komunitas?')">
                                            <i class="fas fa-bullhorn mr-1"></i>
                                            Publikasi
                                        </button>
                                    </form>
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
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Tidak ada submission</h3>
                <p class="text-gray-500">Belum ada submission dengan filter yang dipilih.</p>
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

@push('scripts')
<script>
// Modal functions
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
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush

@endsection