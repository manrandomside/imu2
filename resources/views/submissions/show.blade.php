@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-4xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full {{ $submission->category->color_class ?? 'bg-blue-500' }} flex items-center justify-center">
                    <span class="text-white text-xl">{{ $submission->category->icon ?? '📄' }}</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $submission->title }}</h1>
                    <div class="flex items-center space-x-3 mt-1">
                        <p class="text-gray-600">{{ $submission->category->name ?? 'Unknown Category' }}</p>
                        <span class="text-sm text-gray-500">•</span>
                        <span class="text-sm text-gray-500">ID #{{ str_pad($submission->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-2 rounded-full text-sm font-medium {{ $submission->status_badge_color }}">
                    {{ $submission->status_icon }} {{ $submission->status_display }}
                </span>
                {{-- ✅ ADDED: Category price display --}}
                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-sm">
                    {{ $submission->category->formatted_price ?? 'Rp 0' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 m-4 rounded relative">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 m-4 rounded relative">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif
    @if (session('info'))
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 m-4 rounded relative">
            <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
        </div>
    @endif

    {{-- Content --}}
    <div class="p-6">

        {{-- ✅ ADDED: Quick Actions Card for pending payment --}}
        @if($submission->status === 'pending_payment')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-800">💰 Pembayaran Diperlukan</h3>
                        <p class="text-yellow-700">Lakukan pembayaran sebesar <span class="font-semibold">{{ $submission->category->formatted_price ?? 'Rp 5.000' }}</span> untuk melanjutkan proses review.</p>
                    </div>
                    @if($submission->canBePaid())
                        <a href="{{ route('payments.create', $submission) }}" 
                           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors whitespace-nowrap">
                            <i class="fas fa-credit-card mr-2"></i>
                            Bayar Sekarang
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- ✅ ADDED: Quick Actions Card for approved submissions --}}
        @if($submission->status === 'approved')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-green-800">🎉 Konten Disetujui!</h3>
                        <p class="text-green-700">Konten Anda telah disetujui dan siap untuk dipublikasikan di komunitas.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ✅ ADDED: Quick Actions Card for published submissions --}}
        @if($submission->status === 'published')
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-bullhorn text-purple-600 text-2xl"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-purple-800">🚀 Konten Dipublikasikan!</h3>
                        <p class="text-purple-700">Konten Anda telah dipublikasikan dan dapat dilihat oleh komunitas.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Status Progress --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📈 Status Progress</h3>
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
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-2 border-2 transition-all duration-300 {{ $isActive ? 'bg-' . $step['color'] . '-500 text-white border-' . $step['color'] . '-500' : 'bg-gray-100 text-gray-400 border-gray-200' }} {{ $isCurrent ? 'scale-110 shadow-lg' : '' }}">
                            <i class="{{ $step['icon'] }} {{ $isCurrent ? 'text-lg' : '' }}"></i>
                        </div>
                        <span class="text-xs text-center {{ $isCurrent ? 'font-semibold text-' . $step['color'] . '-600' : 'text-gray-500' }}">
                            {{ $step['label'] }}
                        </span>
                        {{-- ✅ ADDED: Timestamp for completed steps --}}
                        @if($isActive && !$isCurrent)
                            <span class="text-xs text-gray-400 mt-1">✓</span>
                        @endif
                    </div>
                    
                    @if(!$loop->last)
                        <div class="flex-1 h-1 rounded {{ $isActive && $stepIndex < $currentStepIndex ? 'bg-gradient-to-r from-green-400 to-green-500' : 'bg-gray-200' }} mb-6 transition-all duration-500"></div>
                    @endif
                @endforeach
            </div>
            
            {{-- Rejected Status --}}
            @if($submission->status === 'rejected')
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-times-circle text-red-600 text-xl mt-1"></i>
                        <div class="flex-1">
                            <h4 class="font-semibold text-red-800">❌ Konten Ditolak</h4>
                            @if($submission->rejection_reason)
                                <p class="text-red-700 text-sm mt-2 bg-white p-3 rounded border">
                                    <strong>Alasan:</strong> {{ $submission->rejection_reason }}
                                </p>
                            @endif
                            {{-- ✅ ADDED: Edit suggestion --}}
                            @if($submission->canBeEdited())
                                <div class="mt-3">
                                    <a href="{{ route('submissions.edit', $submission) }}" 
                                       class="text-red-600 hover:text-red-800 font-medium underline text-sm">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit dan Kirim Ulang →
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Content Details --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📝 Detail Konten</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                
                {{-- ✅ ADDED: Category info --}}
                <div class="flex items-center justify-between mb-4 p-3 bg-white rounded border">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">{{ $submission->category->icon ?? '📄' }}</span>
                        <div>
                            <h4 class="font-semibold text-gray-800">{{ $submission->category->name ?? 'Unknown Category' }}</h4>
                            <p class="text-sm text-gray-600">{{ $submission->category->description ?? 'No description available' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-blue-600">{{ $submission->category->formatted_price ?? 'Rp 0' }}</p>
                        @if(isset($submission->category->max_file_size))
                            <p class="text-xs text-gray-500">Max: {{ $submission->category->formatted_max_file_size }}</p>
                        @endif
                    </div>
                </div>

                <div class="prose max-w-none">
                    <h4 class="text-md font-semibold text-gray-700 mb-2">Deskripsi:</h4>
                    <div class="bg-white p-4 rounded border">
                        <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $submission->description }}</p>
                    </div>
                </div>
                
                {{-- Attachment --}}
                @if($submission->hasAttachment())
                    <div class="mt-4">
                        <h4 class="text-md font-semibold text-gray-700 mb-2">📎 Lampiran:</h4>
                        <div class="p-3 bg-white border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <i class="{{ $submission->attachment_icon ?? 'fas fa-file' }} {{ $submission->attachment_color ?? 'text-gray-500' }} text-xl"></i>
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $submission->attachment_name ?? 'Unknown File' }}</p>
                                        <p class="text-sm text-gray-600">
                                            {{ $submission->formatted_file_size ?? 'Unknown Size' }}
                                            @if($submission->attachment_type)
                                                • {{ strtoupper(pathinfo($submission->attachment_name, PATHINFO_EXTENSION) ?? '') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ route('submissions.download', $submission) }}" 
                                   class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm transition-colors">
                                    <i class="fas fa-download mr-1"></i>
                                    Download
                                </a>
                            </div>
                            
                            {{-- Image Preview --}}
                            @if($submission->isImageAttachment())
                                <div class="mt-3">
                                    <img src="{{ $submission->attachment_url }}" 
                                         alt="Attachment Preview" 
                                         class="max-w-full h-auto rounded border max-h-64 object-contain bg-white">
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-4">
                        <h4 class="text-md font-semibold text-gray-700 mb-2">📎 Lampiran:</h4>
                        <div class="p-3 bg-white border border-gray-200 rounded-lg text-gray-500 text-sm">
                            <i class="fas fa-file-slash mr-2"></i>Tidak ada lampiran
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Payment Information --}}
        @if($submission->payment)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">💳 Informasi Pembayaran</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status Pembayaran</label>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="px-3 py-1 rounded-full text-sm {{ $submission->payment->status_badge_color ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $submission->payment->status_icon ?? '💰' }} {{ $submission->payment->status_display ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Jumlah</label>
                            <p class="text-lg font-bold text-gray-800">{{ $submission->payment->formatted_amount ?? 'Rp 0' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Metode Pembayaran</label>
                            <p class="text-gray-800">{{ $submission->payment->payment_method_display ?? 'Unknown' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Tanggal Submit</label>
                            <p class="text-gray-800">{{ $submission->payment->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                    
                    {{-- ✅ ENHANCED: Payment status cards --}}
                    @if($submission->payment->status === 'confirmed' && $submission->payment->confirmed_at)
                        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded">
                            <p class="text-green-800 text-sm">
                                <i class="fas fa-check-circle mr-1"></i>
                                <strong>Pembayaran dikonfirmasi</strong> pada {{ $submission->payment->confirmed_at->format('d M Y, H:i') }}
                                @if($submission->payment->confirmedBy)
                                    oleh {{ $submission->payment->confirmedBy->full_name }}
                                @endif
                            </p>
                        </div>
                    @elseif($submission->payment->status === 'rejected')
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                            <p class="text-red-800 text-sm font-medium">
                                <i class="fas fa-times-circle mr-1"></i>
                                <strong>Pembayaran ditolak</strong>
                            </p>
                            @if($submission->payment->rejection_reason)
                                <p class="text-red-700 text-sm mt-1 bg-white p-2 rounded border">
                                    <strong>Alasan:</strong> {{ $submission->payment->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    @elseif($submission->payment->status === 'pending')
                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-yellow-800 text-sm">
                                <i class="fas fa-clock mr-1"></i>
                                <strong>Pembayaran sedang diverifikasi</strong> oleh admin. Mohon tunggu konfirmasi.
                            </p>
                        </div>
                    @endif
                    
                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('payments.show', $submission->payment) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i>
                            Lihat Detail Pembayaran
                        </a>
                        
                        {{-- ✅ ADDED: Payment proof link --}}
                        @if($submission->payment->payment_proof_path)
                            <a href="{{ $submission->payment->payment_proof_url }}" 
                               target="_blank"
                               class="text-green-600 hover:text-green-800 text-sm">
                                <i class="fas fa-receipt mr-1"></i>
                                Lihat Bukti Pembayaran
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @else
            {{-- ✅ ADDED: No payment info card --}}
            @if($submission->status === 'pending_payment')
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">💳 Informasi Pembayaran</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                            <div>
                                <p class="text-yellow-800 font-medium">Pembayaran belum dilakukan</p>
                                <p class="text-yellow-700 text-sm">Silakan lakukan pembayaran untuk melanjutkan proses review.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        {{-- Approval Information --}}
        @if($submission->approved_by && $submission->approved_at)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">✅ Informasi Persetujuan</h3>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-user-check text-green-600 text-xl"></i>
                        <div>
                            <p class="text-green-800 font-medium">
                                Disetujui oleh {{ $submission->approvedBy->full_name }}
                            </p>
                            <p class="text-green-700 text-sm">
                                {{ $submission->approved_at->format('d M Y, H:i') }}
                                ({{ $submission->approved_at->diffForHumans() }})
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ✅ ADDED: Timeline Section --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📅 Timeline Aktivitas</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="space-y-3">
                    {{-- Created --}}
                    <div class="flex items-center space-x-3 text-sm">
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
                            <i class="fas fa-plus text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">Konten dibuat</p>
                            <p class="text-gray-600">{{ $submission->created_at->format('d M Y, H:i') }} ({{ $submission->created_at->diffForHumans() }})</p>
                        </div>
                    </div>

                    {{-- Payment --}}
                    @if($submission->payment)
                        <div class="flex items-center space-x-3 text-sm">
                            <div class="w-8 h-8 rounded-full {{ $submission->payment->isConfirmed() ? 'bg-green-500' : ($submission->payment->isRejected() ? 'bg-red-500' : 'bg-yellow-500') }} flex items-center justify-center text-white">
                                <i class="fas fa-credit-card text-xs"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Pembayaran {{ $submission->payment->status_display }}</p>
                                <p class="text-gray-600">{{ $submission->payment->created_at->format('d M Y, H:i') }} ({{ $submission->payment->created_at->diffForHumans() }})</p>
                            </div>
                        </div>
                    @endif

                    {{-- Submitted --}}
                    @if($submission->submitted_at)
                        <div class="flex items-center space-x-3 text-sm">
                            <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white">
                                <i class="fas fa-paper-plane text-xs"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Dikirim untuk review</p>
                                <p class="text-gray-600">{{ $submission->submitted_at->format('d M Y, H:i') }} ({{ $submission->submitted_at->diffForHumans() }})</p>
                            </div>
                        </div>
                    @endif

                    {{-- Approved/Rejected --}}
                    @if($submission->approved_at)
                        <div class="flex items-center space-x-3 text-sm">
                            <div class="w-8 h-8 rounded-full {{ $submission->status === 'rejected' ? 'bg-red-500' : 'bg-green-500' }} flex items-center justify-center text-white">
                                <i class="fas {{ $submission->status === 'rejected' ? 'fa-times' : 'fa-check' }} text-xs"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">
                                    {{ $submission->status === 'rejected' ? 'Ditolak' : 'Disetujui' }}
                                    @if($submission->approvedBy)
                                        oleh {{ $submission->approvedBy->full_name }}
                                    @endif
                                </p>
                                <p class="text-gray-600">{{ $submission->approved_at->format('d M Y, H:i') }} ({{ $submission->approved_at->diffForHumans() }})</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Metadata --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">ℹ️ Informasi Tambahan</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="font-medium text-gray-600">Dibuat pada</label>
                        <p class="text-gray-800">{{ $submission->created_at->format('d M Y, H:i') }}</p>
                        <p class="text-gray-500 text-xs">{{ $submission->created_at->diffForHumans() }}</p>
                    </div>
                    <div>
                        <label class="font-medium text-gray-600">Terakhir diupdate</label>
                        <p class="text-gray-800">{{ $submission->updated_at->format('d M Y, H:i') }}</p>
                        <p class="text-gray-500 text-xs">{{ $submission->updated_at->diffForHumans() }}</p>
                    </div>
                    @if($submission->submitted_at)
                        <div>
                            <label class="font-medium text-gray-600">Disubmit untuk review</label>
                            <p class="text-gray-800">{{ $submission->submitted_at->format('d M Y, H:i') }}</p>
                            <p class="text-gray-500 text-xs">{{ $submission->submitted_at->diffForHumans() }}</p>
                        </div>
                    @endif
                    <div>
                        <label class="font-medium text-gray-600">ID Submission</label>
                        <p class="text-gray-800 font-mono">#{{ str_pad($submission->id, 6, '0', STR_PAD_LEFT) }}</p>
                        <p class="text-gray-500 text-xs">Gunakan ID ini untuk referensi</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-3 sm:space-y-0">
            <a href="{{ route('submissions.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Dashboard
            </a>
            
            <div class="flex flex-wrap items-center gap-3">
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
                
                {{-- ✅ ENHANCED: Delete button with better conditional --}}
                @if($submission->canBeDeleted())
                    <form method="POST" action="{{ route('submissions.destroy', $submission) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus konten ini? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Hapus
                        </button>
                    </form>
                @endif

                {{-- ✅ ADDED: Print/Export button --}}
                <button onclick="window.print()" 
                        class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    <i class="fas fa-print mr-2"></i>
                    Print
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ✅ ADDED: Print styles --}}
<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .main-card { box-shadow: none !important; }
}
</style>

@endsection