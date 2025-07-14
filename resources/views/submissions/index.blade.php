@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-6xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center">
                    <i class="fas fa-file-alt text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard Konten</h1>
                    <p class="text-gray-600">Kelola dan pantau status submission konten Anda</p>
                </div>
            </div>
            <a href="{{ route('submissions.create') }}" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Submit Konten Baru
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 m-4 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 m-4 rounded">
            {{ session('error') }}
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="p-6 border-b border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</div>
                <div class="text-sm text-blue-800">Total Konten</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending_payment'] }}</div>
                <div class="text-sm text-yellow-800">Menunggu Bayar</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-purple-600">{{ $stats['pending_approval'] }}</div>
                <div class="text-sm text-purple-800">Menunggu Review</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['approved'] + $stats['published'] }}</div>
                <div class="text-sm text-green-800">Disetujui</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-red-600">{{ $stats['rejected'] }}</div>
                <div class="text-sm text-red-800">Ditolak</div>
            </div>
        </div>
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
                                    <h3 class="font-semibold text-lg text-gray-800">{{ $submission->title }}</h3>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $submission->status_badge_color }}">
                                        {{ $submission->status_icon }} {{ $submission->status_display }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center space-x-4 text-sm text-gray-600 mb-2">
                                    <span>
                                        <i class="fas fa-folder mr-1"></i>
                                        {{ $submission->category->name }}
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ $submission->created_at->format('d M Y, H:i') }}
                                    </span>
                                    @if($submission->hasAttachment())
                                        <span>
                                            <i class="{{ $submission->attachment_icon }} mr-1 {{ $submission->attachment_color }}"></i>
                                            {{ $submission->attachment_name }}
                                        </span>
                                    @endif
                                </div>
                                
                                <p class="text-gray-700 text-sm line-clamp-2">
                                    {{ Str::limit($submission->description, 150) }}
                                </p>
                                
                                {{-- Payment Info --}}
                                @if($submission->payment)
                                    <div class="mt-2 flex items-center space-x-2">
                                        <span class="text-xs text-gray-500">Pembayaran:</span>
                                        <span class="px-2 py-1 rounded-full text-xs {{ $submission->payment->status_badge_color }}">
                                            {{ $submission->payment->status_icon }} {{ $submission->payment->status_display }}
                                        </span>
                                        @if($submission->payment->status === 'confirmed')
                                            <span class="text-xs text-gray-500">
                                                - {{ $submission->payment->confirmed_at->format('d M Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                
                                {{-- Rejection Reason --}}
                                @if($submission->status === 'rejected' && $submission->rejection_reason)
                                    <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                                        <strong>Alasan ditolak:</strong> {{ $submission->rejection_reason }}
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
                                
                                @if($submission->canBePaid())
                                    <a href="{{ route('payments.create', $submission) }}" 
                                       class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-credit-card mr-1"></i>
                                        Bayar
                                    </a>
                                @endif
                                
                                @if($submission->canBeEdited())
                                    <a href="{{ route('submissions.edit', $submission) }}" 
                                       class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </a>
                                @endif
                                
                                @if($submission->payment && $submission->payment->status === 'rejected')
                                    <a href="{{ route('payments.edit', $submission->payment) }}" 
                                       class="px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-redo mr-1"></i>
                                        Bayar Ulang
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Pagination --}}
            @if($submissions->hasPages())
                <div class="mt-6">
                    {{ $submissions->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📝</div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum ada konten</h3>
                <p class="text-gray-500 mb-6">Mulai submit konten pertama Anda untuk dipublikasikan di komunitas IMU.</p>
                <a href="{{ route('submissions.create') }}" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Submit Konten Pertama
                </a>
            </div>
        @endif
    </div>

    {{-- Categories Quick Access --}}
    @if($categories->count() > 0)
        <div class="border-t border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Submit Konten Baru</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($categories as $category)
                    <a href="{{ route('submissions.create', ['category' => $category->id]) }}" 
                       class="block p-4 border border-gray-200 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-all">
                        <div class="flex items-center space-x-3">
                            <div class="text-2xl">{{ $category->icon }}</div>
                            <div>
                                <h4 class="font-medium text-gray-800">{{ $category->name }}</h4>
                                <p class="text-sm text-blue-600 font-semibold">{{ $category->formatted_price }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>

@push('scripts')
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