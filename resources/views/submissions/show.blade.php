@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-4xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full {{ $submission->category->color_class }} flex items-center justify-center">
                    <span class="text-white text-xl">{{ $submission->category->icon }}</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $submission->title }}</h1>
                    <p class="text-gray-600">{{ $submission->category->name }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-2 rounded-full text-sm font-medium {{ $submission->status_badge_color }}">
                    {{ $submission->status_icon }} {{ $submission->status_display }}
                </span>
            </div>
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

    {{-- Content --}}
    <div class="p-6">
        {{-- Status Progress --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Progress</h3>
            <div class="flex items-center space-x-4">
                @php
                    $steps = [
                        'pending_payment' => ['icon' => 'fas fa-credit-card', 'label' => 'Pembayaran', 'color' => 'blue'],
                        'pending_approval' => ['icon' => 'fas fa-clock', 'label' => 'Review', 'color' => 'yellow'],
                        'approved' => ['icon' => 'fas fa-check', 'label' => 'Disetujui', 'color' => 'green'],
                        'published' => ['icon' => 'fas fa-bullhorn', 'label' => 'Dipublikasi', 'color' => 'purple']
                    ];
                    
                    $currentStepIndex = array_search($submission->status, array_keys($steps));
                    if ($submission->status === 'rejected') {
                        $currentStepIndex = -1; // Special case for rejected
                    }
                @endphp
                
                @foreach($steps as $stepKey => $step)
                    @php
                        $stepIndex = array_search($stepKey, array_keys($steps));
                        $isActive = $stepIndex <= $currentStepIndex;
                        $isCurrent = $stepKey === $submission->status;
                    @endphp
                    
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2 {{ $isActive ? 'bg-' . $step['color'] . '-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                            <i class="{{ $step['icon'] }}"></i>
                        </div>
                        <span class="text-xs text-center {{ $isCurrent ? 'font-semibold text-' . $step['color'] . '-600' : 'text-gray-500' }}">
                            {{ $step['label'] }}
                        </span>
                    </div>
                    
                    @if(!$loop->last)
                        <div class="flex-1 h-0.5 {{ $isActive && $stepIndex < $currentStepIndex ? 'bg-green-400' : 'bg-gray-200' }} mb-6"></div>
                    @endif
                @endforeach
            </div>
            
            {{-- Rejected Status --}}
            @if($submission->status === 'rejected')
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center space-x-2 text-red-600">
                        <i class="fas fa-times-circle"></i>
                        <span class="font-semibold">Konten Ditolak</span>
                    </div>
                    @if($submission->rejection_reason)
                        <p class="text-red-700 text-sm mt-2">{{ $submission->rejection_reason }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Content Details --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Konten</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $submission->description }}</p>
                </div>
                
                {{-- Attachment --}}
                @if($submission->hasAttachment())
                    <div class="mt-4 p-3 bg-white border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <i class="{{ $submission->attachment_icon }} {{ $submission->attachment_color }} text-xl"></i>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $submission->attachment_name }}</p>
                                    <p class="text-sm text-gray-600">{{ $submission->formatted_file_size }}</p>
                                </div>
                            </div>
                            <a href="{{ route('submissions.download', $submission) }}" 
                               class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm transition-colors">
                                <i class="fas fa-download mr-1"></i>
                                Download
                            </a>
                        </div>
                        
                        {{-- Image Preview --}}
                        @if($submission->isImageAttachment())
                            <div class="mt-3">
                                <img src="{{ $submission->attachment_url }}" 
                                     alt="Attachment" 
                                     class="max-w-full h-auto rounded border max-h-64 object-contain">
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Payment Information --}}
        @if($submission->payment)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Pembayaran</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status Pembayaran</label>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="px-2 py-1 rounded-full text-xs {{ $submission->payment->status_badge_color }}">
                                    {{ $submission->payment->status_icon }} {{ $submission->payment->status_display }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Jumlah</label>
                            <p class="text-lg font-bold text-gray-800">{{ $submission->payment->formatted_amount }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Metode Pembayaran</label>
                            <p class="text-gray-800">{{ $submission->payment->payment_method_display }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Tanggal Submit</label>
                            <p class="text-gray-800">{{ $submission->payment->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                    
                    @if($submission->payment->status === 'confirmed' && $submission->payment->confirmed_at)
                        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded">
                            <p class="text-green-800 text-sm">
                                <i class="fas fa-check-circle mr-1"></i>
                                Pembayaran dikonfirmasi pada {{ $submission->payment->confirmed_at->format('d M Y, H:i') }}
                                @if($submission->payment->confirmedBy)
                                    oleh {{ $submission->payment->confirmedBy->full_name }}
                                @endif
                            </p>
                        </div>
                    @elseif($submission->payment->status === 'rejected')
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                            <p class="text-red-800 text-sm font-medium">
                                <i class="fas fa-times-circle mr-1"></i>
                                Pembayaran ditolak
                            </p>
                            @if($submission->payment->rejection_reason)
                                <p class="text-red-700 text-sm mt-1">{{ $submission->payment->rejection_reason }}</p>
                            @endif
                        </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ route('payments.show', $submission->payment) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i>
                            Lihat Detail Pembayaran
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Approval Information --}}
        @if($submission->approved_by && $submission->approved_at)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Persetujuan</h3>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-user-check text-green-600 text-xl"></i>
                        <div>
                            <p class="text-green-800 font-medium">
                                Disetujui oleh {{ $submission->approvedBy->full_name }}
                            </p>
                            <p class="text-green-700 text-sm">
                                {{ $submission->approved_at->format('d M Y, H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Metadata --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Tambahan</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="font-medium text-gray-600">Dibuat pada</label>
                        <p class="text-gray-800">{{ $submission->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <label class="font-medium text-gray-600">Terakhir diupdate</label>
                        <p class="text-gray-800">{{ $submission->updated_at->format('d M Y, H:i') }}</p>
                    </div>
                    @if($submission->submitted_at)
                        <div>
                            <label class="font-medium text-gray-600">Disubmit untuk review</label>
                            <p class="text-gray-800">{{ $submission->submitted_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif
                    <div>
                        <label class="font-medium text-gray-600">ID Submission</label>
                        <p class="text-gray-800 font-mono">#{{ str_pad($submission->id, 6, '0', STR_PAD_LEFT) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('submissions.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Dashboard
            </a>
            
            <div class="flex items-center space-x-3">
                @if($submission->canBePaid())
                    <a href="{{ route('payments.create', $submission) }}" 
                       class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-credit-card mr-2"></i>
                        Lakukan Pembayaran
                    </a>
                @endif
                
                @if($submission->canBeEdited())
                    <a href="{{ route('submissions.edit', $submission) }}" 
                       class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Konten
                    </a>
                @endif
                
                @if($submission->payment && $submission->payment->status === 'rejected')
                    <a href="{{ route('payments.edit', $submission->payment) }}" 
                       class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-redo mr-2"></i>
                        Kirim Ulang Pembayaran
                    </a>
                @endif
                
                @if(in_array($submission->status, ['pending_payment', 'rejected']))
                    <form method="POST" action="{{ route('submissions.destroy', $submission) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus konten ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Hapus
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection