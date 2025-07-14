@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-4xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-blue-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center">
                    <i class="fas fa-credit-card text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Pembayaran</h1>
                    <p class="text-gray-600">{{ $payment->submission->title }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-2 rounded-full text-sm font-medium {{ $payment->status_badge_color }}">
                    {{ $payment->status_icon }} {{ $payment->status_display }}
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
        {{-- Payment Status Progress --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Pembayaran</h3>
            <div class="flex items-center space-x-4">
                @php
                    $steps = [
                        'pending' => ['icon' => 'fas fa-clock', 'label' => 'Menunggu', 'color' => 'yellow'],
                        'confirmed' => ['icon' => 'fas fa-check', 'label' => 'Dikonfirmasi', 'color' => 'green'],
                    ];
                    
                    $currentStepIndex = array_search($payment->status, array_keys($steps));
                    if ($payment->status === 'rejected') {
                        $currentStepIndex = -1; // Special case for rejected
                    }
                @endphp
                
                @foreach($steps as $stepKey => $step)
                    @php
                        $stepIndex = array_search($stepKey, array_keys($steps));
                        $isActive = $stepIndex <= $currentStepIndex;
                        $isCurrent = $stepKey === $payment->status;
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
            @if($payment->status === 'rejected')
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center space-x-2 text-red-600">
                        <i class="fas fa-times-circle"></i>
                        <span class="font-semibold">Pembayaran Ditolak</span>
                    </div>
                    @if($payment->rejection_reason)
                        <p class="text-red-700 text-sm mt-2">{{ $payment->rejection_reason }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Payment Details --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Pembayaran</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">ID Pembayaran</label>
                        <p class="text-gray-800 font-mono">#{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Jumlah</label>
                        <p class="text-xl font-bold text-gray-800">{{ $payment->formatted_amount }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Metode Pembayaran</label>
                        <p class="text-gray-800">{{ $payment->payment_method_display }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Tanggal Submit</label>
                        <p class="text-gray-800">{{ $payment->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submission Details --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Konten</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-start space-x-4">
                    <div class="text-3xl">{{ $payment->submission->category->icon }}</div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 mb-1">{{ $payment->submission->title }}</h4>
                        <p class="text-sm text-gray-600 mb-2">Kategori: {{ $payment->submission->category->name }}</p>
                        <p class="text-gray-700 text-sm">{{ Str::limit($payment->submission->description, 200) }}</p>
                        <div class="mt-2">
                            <a href="{{ route('submissions.show', $payment->submission) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                Lihat Detail Konten
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Details --}}
        @if($payment->payment_details)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Transfer</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        @if(isset($payment->payment_details['sender_name']))
                            <div>
                                <label class="font-medium text-gray-600">Nama Pengirim</label>
                                <p class="text-gray-800">{{ $payment->payment_details['sender_name'] }}</p>
                            </div>
                        @endif
                        @if(isset($payment->payment_details['sender_account']))
                            <div>
                                <label class="font-medium text-gray-600">Rekening Pengirim</label>
                                <p class="text-gray-800">{{ $payment->payment_details['sender_account'] }}</p>
                            </div>
                        @endif
                        @if(isset($payment->payment_details['transaction_id']))
                            <div>
                                <label class="font-medium text-gray-600">ID Transaksi</label>
                                <p class="text-gray-800">{{ $payment->payment_details['transaction_id'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Payment Proof --}}
        @if($payment->payment_proof_path)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Bukti Pembayaran</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-center">
                        <img src="{{ $payment->payment_proof_url }}" 
                             alt="Bukti Pembayaran" 
                             class="max-w-full h-auto rounded border max-h-96 object-contain mx-auto mb-3">
                        <a href="{{ route('payments.download_proof', $payment) }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Download Bukti Pembayaran
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Confirmation Info --}}
        @if($payment->status === 'confirmed' && $payment->confirmed_at)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Konfirmasi</h3>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-user-check text-green-600 text-xl"></i>
                        <div>
                            <p class="text-green-800 font-medium">
                                Dikonfirmasi oleh {{ $payment->confirmedBy->full_name }}
                            </p>
                            <p class="text-green-700 text-sm">
                                {{ $payment->confirmed_at->format('d M Y, H:i') }}
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
                        <p class="text-gray-800">{{ $payment->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <label class="font-medium text-gray-600">Terakhir diupdate</label>
                        <p class="text-gray-800">{{ $payment->updated_at->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <label class="font-medium text-gray-600">User</label>
                        <p class="text-gray-800">{{ $payment->user->full_name }}</p>
                    </div>
                    <div>
                        <label class="font-medium text-gray-600">Status</label>
                        <span class="px-2 py-1 rounded-full text-xs {{ $payment->status_badge_color }}">
                            {{ $payment->status_icon }} {{ $payment->status_display }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('submissions.show', $payment->submission) }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Konten
            </a>
            
            <div class="flex items-center space-x-3">
                @if($payment->status === 'rejected')
                    <a href="{{ route('payments.edit', $payment) }}" 
                       class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-edit mr-2"></i>
                        Edit & Kirim Ulang
                    </a>
                @endif
                
                @if($payment->payment_proof_path)
                    <a href="{{ route('payments.download_proof', $payment) }}" 
                       class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Download Bukti
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection