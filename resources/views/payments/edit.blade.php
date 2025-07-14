@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-4xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-red-50">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full bg-orange-500 flex items-center justify-center">
                <i class="fas fa-redo text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Edit Pembayaran</h1>
                <p class="text-gray-600">Perbaiki dan kirim ulang pembayaran Anda</p>
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

    {{-- Rejection Info --}}
    @if($payment->status === 'rejected' && $payment->rejection_reason)
        <div class="m-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center space-x-2 mb-2">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
                <h3 class="font-semibold text-red-800">Alasan Penolakan</h3>
            </div>
            <p class="text-red-700 text-sm">{{ $payment->rejection_reason }}</p>
        </div>
    @endif

    {{-- Content Summary --}}
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan Konten</h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-start space-x-4">
                <div class="text-3xl">{{ $payment->submission->category->icon }}</div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800 mb-1">{{ $payment->submission->title }}</h4>
                    <p class="text-sm text-gray-600 mb-2">Kategori: {{ $payment->submission->category->name }}</p>
                    <p class="text-gray-700 text-sm">{{ Str::limit($payment->submission->description, 200) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Form --}}
    <form method="POST" action="{{ route('payments.update', $payment) }}" enctype="multipart/form-data" class="p-6">
        @csrf
        @method('PUT')
        
        {{-- Payment Amount --}}
        <div class="mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-blue-800">Total Pembayaran</h3>
                        <p class="text-sm text-blue-600">Biaya submit konten {{ $payment->submission->category->name }}</p>
                    </div>
                    <div class="text-2xl font-bold text-blue-600">{{ $payment->formatted_amount }}</div>
                </div>
            </div>
        </div>

        {{-- Payment Method Selection --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-4">
                Pilih Metode Pembayaran <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($paymentMethods as $key => $method)
                    <div class="relative">
                        <input type="radio" 
                               id="payment_{{ $key }}" 
                               name="payment_method" 
                               value="{{ $key }}"
                               class="peer hidden"
                               {{ old('payment_method', $payment->payment_method) == $key ? 'checked' : '' }}>
                        <label for="payment_{{ $key }}" 
                               class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                            <div class="flex items-center space-x-3">
                                <i class="{{ $method['icon'] }} text-2xl text-gray-600"></i>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800">{{ $method['name'] }}</h4>
                                    <p class="text-sm text-gray-600">{{ $method['details'] }}</p>
                                </div>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>
            @error('payment_method')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Bank Transfer Details (shown when transfer_bank is selected) --}}
        <div id="bank-details" class="mb-6 hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Pengirim <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="sender_name" 
                           name="payment_details[sender_name]" 
                           value="{{ old('payment_details.sender_name', $payment->payment_details['sender_name'] ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 @error('payment_details.sender_name') border-red-500 @enderror"
                           placeholder="Nama sesuai rekening">
                    @error('payment_details.sender_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="sender_account" class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Rekening Pengirim <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="sender_account" 
                           name="payment_details[sender_account]" 
                           value="{{ old('payment_details.sender_account', $payment->payment_details['sender_account'] ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 @error('payment_details.sender_account') border-red-500 @enderror"
                           placeholder="Nomor rekening pengirim">
                    @error('payment_details.sender_account')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Transaction ID (for e-wallet) --}}
        <div id="transaction-details" class="mb-6 hidden">
            <label for="transaction_id" class="block text-sm font-medium text-gray-700 mb-2">
                ID Transaksi (Opsional)
            </label>
            <input type="text" 
                   id="transaction_id" 
                   name="payment_details[transaction_id]" 
                   value="{{ old('payment_details.transaction_id', $payment->payment_details['transaction_id'] ?? '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                   placeholder="ID transaksi dari aplikasi">
        </div>

        {{-- Current Payment Proof --}}
        @if($payment->payment_proof_path)
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bukti Pembayaran Saat Ini</label>
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <div class="text-center">
                        <img src="{{ $payment->payment_proof_url }}" 
                             alt="Current Payment Proof" 
                             class="max-w-full h-auto rounded border max-h-48 object-contain mx-auto mb-2">
                        <p class="text-sm text-gray-600">Bukti pembayaran yang sudah diunggah</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- New Payment Proof Upload --}}
        <div class="mb-6">
            <label for="payment_proof" class="block text-sm font-medium text-gray-700 mb-2">
                {{ $payment->payment_proof_path ? 'Ganti Bukti Pembayaran' : 'Bukti Pembayaran' }} <span class="text-red-500">*</span>
            </label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                <input type="file" 
                       id="payment_proof" 
                       name="payment_proof" 
                       class="hidden"
                       accept="image/*">
                <div id="proof-drop-area" onclick="document.getElementById('payment_proof').click()" class="cursor-pointer">
                    <i class="fas fa-camera text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600 mb-1">Klik untuk upload bukti pembayaran baru</p>
                    <p class="text-sm text-gray-500">Format: JPG, PNG - Max 5MB</p>
                </div>
                <div id="proof-preview" class="mt-4 hidden">
                    <img id="proof-image" class="max-w-full h-48 object-contain mx-auto rounded">
                    <div class="mt-2">
                        <span id="proof-name" class="text-sm text-gray-700"></span>
                        <button type="button" onclick="clearProofInput()" class="ml-2 text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
            @error('payment_proof')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Information Box --}}
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
            <div class="flex items-start space-x-3">
                <i class="fas fa-exclamation-triangle text-orange-500 mt-1"></i>
                <div class="text-sm text-orange-800">
                    <h4 class="font-semibold mb-2">Panduan Edit Pembayaran:</h4>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Pastikan bukti pembayaran yang diunggah jelas dan dapat dibaca</li>
                        <li>Nominal transfer harus sesuai dengan yang tertera ({{ $payment->formatted_amount }})</li>
                        <li>Informasi pengirim harus sesuai dengan bukti transfer</li>
                        <li>Pembayaran akan diverifikasi ulang dalam 1x24 jam</li>
                        <li>Pastikan semua data sudah benar sebelum mengirim ulang</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('payments.show', $payment) }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Batal
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium transition-colors">
                <i class="fas fa-paper-plane mr-2"></i>
                Kirim Ulang Pembayaran
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const bankDetails = document.getElementById('bank-details');
    const transactionDetails = document.getElementById('transaction-details');
    const proofInput = document.getElementById('payment_proof');
    const proofPreview = document.getElementById('proof-preview');
    const proofImage = document.getElementById('proof-image');
    const proofName = document.getElementById('proof-name');
    const proofDropArea = document.getElementById('proof-drop-area');

    // Payment method change handler
    paymentMethods.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'transfer_bank') {
                bankDetails.classList.remove('hidden');
                transactionDetails.classList.add('hidden');
            } else {
                bankDetails.classList.add('hidden');
                transactionDetails.classList.remove('hidden');
            }
        });
    });

    // Initialize based on current selection
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (selectedMethod) {
        selectedMethod.dispatchEvent(new Event('change'));
    }

    // Payment proof upload
    proofInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            showProofPreview(file);
        }
    });

    // Drag and drop for proof
    proofDropArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-blue-400', 'bg-blue-50');
    });

    proofDropArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-blue-400', 'bg-blue-50');
    });

    proofDropArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            proofInput.files = files;
            showProofPreview(files[0]);
        }
    });

    function showProofPreview(file) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                proofImage.src = e.target.result;
                proofName.textContent = file.name;
                proofPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    window.clearProofInput = function() {
        proofInput.value = '';
        proofPreview.classList.add('hidden');
    };
});
</script>
@endpush

@endsection