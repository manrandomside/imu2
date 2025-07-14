@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-7xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-blue-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center">
                    <i class="fas fa-credit-card text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard - Payments</h1>
                    <p class="text-gray-600">Kelola dan konfirmasi pembayaran submission</p>
                </div>
            </div>
            <a href="{{ route('admin.submissions.index') }}" 
               class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-tasks mr-2"></i>
                Kelola Submissions
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="p-6 border-b border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['total_payments'] }}</div>
                <div class="text-sm text-blue-800">Total Pembayaran</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending_payments'] }}</div>
                <div class="text-sm text-yellow-800">Menunggu Konfirmasi</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['confirmed_payments'] }}</div>
                <div class="text-sm text-green-800">Dikonfirmasi</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg text-center">
                <div class="text-2xl font-bold text-red-600">{{ $stats['rejected_payments'] }}</div>
                <div class="text-sm text-red-800">Ditolak</div>
            </div>
        </div>
        
        {{-- Revenue Info --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-green-100 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-green-800">Total Revenue</h3>
                        <p class="text-sm text-green-600">Semua pembayaran terkonfirmasi</p>
                    </div>
                    <div class="text-2xl font-bold text-green-600">
                        Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="p-4 bg-blue-100 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-blue-800">Revenue Bulan Ini</h3>
                        <p class="text-sm text-blue-600">{{ now()->format('F Y') }}</p>
                    </div>
                    <div class="text-2xl font-bold text-blue-600">
                        Rp {{ number_format($stats['revenue_this_month'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="p-6 border-b border-gray-200 bg-gray-50">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                    <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Metode</label>
                <select name="method" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Semua Metode</option>
                    <option value="transfer_bank" {{ $method === 'transfer_bank' ? 'selected' : '' }}>Transfer Bank</option>
                    <option value="dana" {{ $method === 'dana' ? 'selected' : '' }}>DANA</option>
                    <option value="gopay" {{ $method === 'gopay' ? 'selected' : '' }}>GoPay</option>
                    <option value="ovo" {{ $method === 'ovo' ? 'selected' : '' }}>OVO</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" 
                       class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" 
                       class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm transition-colors">
                    <i class="fas fa-search mr-1"></i>
                    Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Payments List --}}
    <div class="p-6">
        @if($payments->count() > 0)
            {{-- Bulk Actions --}}
            @if($status === 'pending')
                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-yellow-800">Aksi Massal</h3>
                            <p class="text-sm text-yellow-600">Pilih pembayaran dan lakukan aksi massal</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="selectAll()" class="text-sm text-blue-600 hover:text-blue-800">Pilih Semua</button>
                            <button onclick="confirmSelected()" class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm">
                                Konfirmasi Terpilih
                            </button>
                            <button onclick="rejectSelected()" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
                                Tolak Terpilih
                            </button>
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="space-y-4">
                @foreach($payments as $payment)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                @if($status === 'pending')
                                    <input type="checkbox" class="payment-checkbox mt-1" value="{{ $payment->id }}">
                                @endif
                                
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="font-semibold text-lg text-gray-800">
                                            {{ $payment->submission->title }}
                                        </h3>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $payment->status_badge_color }}">
                                            {{ $payment->status_icon }} {{ $payment->status_display }}
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-600 mb-3">
                                        <div>
                                            <span class="font-medium">User:</span>
                                            <p class="text-gray-800">{{ $payment->user->full_name }}</p>
                                        </div>
                                        <div>
                                            <span class="font-medium">Jumlah:</span>
                                            <p class="text-gray-800 font-bold">{{ $payment->formatted_amount }}</p>
                                        </div>
                                        <div>
                                            <span class="font-medium">Metode:</span>
                                            <p class="text-gray-800">{{ $payment->payment_method_display }}</p>
                                        </div>
                                        <div>
                                            <span class="font-medium">Tanggal:</span>
                                            <p class="text-gray-800">{{ $payment->created_at->format('d M Y, H:i') }}</p>
                                        </div>
                                    </div>
                                    
                                    {{-- Payment Details --}}
                                    @if($payment->payment_details)
                                        <div class="p-3 bg-gray-50 rounded-lg mb-3">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                @if(isset($payment->payment_details['sender_name']))
                                                    <div>
                                                        <span class="font-medium text-gray-600">Nama Pengirim:</span>
                                                        <p class="text-gray-800">{{ $payment->payment_details['sender_name'] }}</p>
                                                    </div>
                                                @endif
                                                @if(isset($payment->payment_details['sender_account']))
                                                    <div>
                                                        <span class="font-medium text-gray-600">Rekening Pengirim:</span>
                                                        <p class="text-gray-800">{{ $payment->payment_details['sender_account'] }}</p>
                                                    </div>
                                                @endif
                                                @if(isset($payment->payment_details['transaction_id']))
                                                    <div>
                                                        <span class="font-medium text-gray-600">ID Transaksi:</span>
                                                        <p class="text-gray-800">{{ $payment->payment_details['transaction_id'] }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    
                                    {{-- Rejection Reason --}}
                                    @if($payment->status === 'rejected' && $payment->rejection_reason)
                                        <div class="p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700 mb-2">
                                            <strong>Alasan ditolak:</strong> {{ $payment->rejection_reason }}
                                        </div>
                                    @endif
                                    
                                    {{-- Confirmation Info --}}
                                    @if($payment->status === 'confirmed' && $payment->confirmed_at)
                                        <div class="p-2 bg-green-50 border border-green-200 rounded text-sm text-green-700 mb-2">
                                            <strong>Dikonfirmasi:</strong> {{ $payment->confirmed_at->format('d M Y, H:i') }}
                                            @if($payment->confirmedBy)
                                                oleh {{ $payment->confirmedBy->full_name }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="flex flex-col space-y-2 ml-4">
                                @if($payment->payment_proof_path)
                                    <button onclick="showProofModal('{{ $payment->payment_proof_url }}', '{{ $payment->user->full_name }}')" 
                                            class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-image mr-1"></i>
                                        Lihat Bukti
                                    </button>
                                @endif
                                
                                <a href="{{ route('payments.show', $payment) }}" 
                                   class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition-colors">
                                    <i class="fas fa-eye mr-1"></i>
                                    Detail
                                </a>
                                
                                @if($payment->canBeConfirmed())
                                    <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm transition-colors w-full"
                                                onclick="return confirm('Konfirmasi pembayaran ini?')">
                                            <i class="fas fa-check mr-1"></i>
                                            Konfirmasi
                                        </button>
                                    </form>
                                    
                                    <button onclick="openRejectModal({{ $payment->id }}, '{{ $payment->user->full_name }}')" 
                                            class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm transition-colors">
                                        <i class="fas fa-times mr-1"></i>
                                        Tolak
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Pagination --}}
            @if($payments->hasPages())
                <div class="mt-6">
                    {{ $payments->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="text-6xl mb-4">💳</div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Tidak ada pembayaran</h3>
                <p class="text-gray-500">Belum ada pembayaran dengan filter yang dipilih.</p>
            </div>
        @endif
    </div>
</div>

{{-- Payment Proof Modal --}}
<div id="proof-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">Bukti Pembayaran - <span id="proof-user"></span></h3>
            <button onclick="closeProofModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="text-center">
            <img id="proof-image" src="" alt="Bukti Pembayaran" class="max-w-full h-auto rounded border">
        </div>
    </div>
</div>

{{-- Rejection Modal --}}
<div id="rejection-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Tolak Pembayaran</h3>
        <p class="text-gray-600 mb-4">Berikan alasan penolakan untuk pembayaran dari <span id="rejection-user"></span>:</p>
        
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
                <button type="button" onclick="closeRejectModal()" 
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
// Proof modal functions
function showProofModal(imageUrl, userName) {
    document.getElementById('proof-image').src = imageUrl;
    document.getElementById('proof-user').textContent = userName;
    document.getElementById('proof-modal').classList.remove('hidden');
}

function closeProofModal() {
    document.getElementById('proof-modal').classList.add('hidden');
}

// Rejection modal functions
function openRejectModal(paymentId, userName) {
    document.getElementById('rejection-user').textContent = userName;
    document.getElementById('rejection-form').action = `/admin/payments/${paymentId}/reject`;
    document.getElementById('rejection-modal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejection-modal').classList.add('hidden');
}

// Bulk actions
function selectAll() {
    const checkboxes = document.querySelectorAll('.payment-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
}

function getSelectedPayments() {
    const checkboxes = document.querySelectorAll('.payment-checkbox:checked');
    return Array.from(checkboxes).map(checkbox => checkbox.value);
}

function confirmSelected() {
    const selected = getSelectedPayments();
    if (selected.length === 0) {
        alert('Pilih pembayaran yang akan dikonfirmasi');
        return;
    }
    
    if (confirm(`Konfirmasi ${selected.length} pembayaran yang dipilih?`)) {
        bulkAction('confirm', selected);
    }
}

function rejectSelected() {
    const selected = getSelectedPayments();
    if (selected.length === 0) {
        alert('Pilih pembayaran yang akan ditolak');
        return;
    }
    
    const reason = prompt('Masukkan alasan penolakan:');
    if (reason && reason.trim()) {
        bulkAction('reject', selected, reason);
    }
}

function bulkAction(action, paymentIds, reason = null) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/payments/bulk-action';
    
    // CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(csrfInput);
    
    // Action
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);
    
    // Payment IDs
    paymentIds.forEach(id => {
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'payment_ids[]';
        idInput.value = id;
        form.appendChild(idInput);
    });
    
    // Rejection reason
    if (reason) {
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'rejection_reason';
        reasonInput.value = reason;
        form.appendChild(reasonInput);
    }
    
    document.body.appendChild(form);
    form.submit();
}

// Close modals when clicking outside
document.getElementById('proof-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProofModal();
    }
});

document.getElementById('rejection-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>
@endpush

@endsection