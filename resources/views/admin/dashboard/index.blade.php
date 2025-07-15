{{-- resources/views/admin/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-7xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-blue-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-purple-500 flex items-center justify-center">
                    <i class="fas fa-tachometer-alt text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
                    <p class="text-gray-600">Kelola pembayaran dan submission secara terintegrasi</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <button onclick="refreshDashboard()" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
                <a href="{{ route('admin.payments.export') }}" 
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Export Data
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Overview --}}
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan Status</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Pending Payments --}}
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending_payments'] }}</div>
                        <div class="text-sm text-yellow-800">Pembayaran Menunggu</div>
                    </div>
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" 
                       class="text-yellow-700 hover:text-yellow-900 text-sm font-medium">
                        Lihat Detail →
                    </a>
                </div>
            </div>

            {{-- Pending Reviews --}}
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-purple-600">{{ $stats['pending_reviews'] }}</div>
                        <div class="text-sm text-purple-800">Butuh Review</div>
                    </div>
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-eye text-purple-600"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('admin.submissions.index', ['status' => 'pending_approval']) }}" 
                       class="text-purple-700 hover:text-purple-900 text-sm font-medium">
                        Lihat Detail →
                    </a>
                </div>
            </div>

            {{-- Ready to Publish --}}
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-blue-600">{{ $stats['ready_to_publish'] }}</div>
                        <div class="text-sm text-blue-800">Siap Publikasi</div>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-rocket text-blue-600"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="{{ route('admin.submissions.index', ['status' => 'approved']) }}" 
                       class="text-blue-700 hover:text-blue-900 text-sm font-medium">
                        Lihat Detail →
                    </a>
                </div>
            </div>

            {{-- Today's Revenue --}}
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-green-600">Rp {{ number_format($stats['today_revenue'], 0, ',', '.') }}</div>
                        <div class="text-sm text-green-800">Revenue Hari Ini</div>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-green-600"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-green-700 text-sm font-medium">
                        {{ $stats['today_payments'] }} pembayaran
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Workflow Progress Table --}}
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Alur Kerja Submission</h2>
            <div class="flex space-x-2">
                <select id="filterStatus" onchange="filterByStatus()" 
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="all">Semua Status</option>
                    <option value="payment_pending">Menunggu Pembayaran</option>
                    <option value="pending_approval">Menunggu Review</option>
                    <option value="approved">Disetujui</option>
                    <option value="published">Dipublikasi</option>
                </select>
                <select id="filterCategory" onchange="filterByCategory()" 
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="all">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Progress Workflow Table --}}
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Konten</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">User</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Progress</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Pembayaran</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($submissions as $submission)
                        <tr class="hover:bg-gray-50 submission-row" 
                            data-status="{{ $submission->status }}" 
                            data-category="{{ $submission->category_id }}">
                            {{-- Content Info --}}
                            <td class="px-4 py-4">
                                <div class="flex items-start space-x-3">
                                    <div class="text-2xl">{{ $submission->category->icon }}</div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ Str::limit($submission->title, 40) }}</h4>
                                        <p class="text-sm text-gray-600">{{ $submission->category->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $submission->created_at->format('d M Y, H:i') }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- User Info --}}
                            <td class="px-4 py-4">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-medium text-blue-600">
                                            {{ strtoupper(substr($submission->user->full_name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $submission->user->full_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $submission->user->prodi }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Progress Tracking --}}
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center space-x-2">
                                        {{-- Payment Step --}}
                                        <div class="flex flex-col items-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $submission->payment && $submission->payment->status === 'confirmed' ? 'bg-green-500 text-white' : ($submission->payment ? 'bg-yellow-500 text-white' : 'bg-gray-300 text-gray-600') }}">
                                                <i class="fas fa-credit-card text-xs"></i>
                                            </div>
                                            <span class="text-xs mt-1">Payment</span>
                                        </div>

                                        {{-- Arrow --}}
                                        <div class="w-4 h-0.5 bg-gray-300"></div>

                                        {{-- Review Step --}}
                                        <div class="flex flex-col items-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $submission->status === 'pending_approval' || $submission->status === 'approved' || $submission->status === 'published' ? 'bg-purple-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                                                <i class="fas fa-eye text-xs"></i>
                                            </div>
                                            <span class="text-xs mt-1">Review</span>
                                        </div>

                                        {{-- Arrow --}}
                                        <div class="w-4 h-0.5 bg-gray-300"></div>

                                        {{-- Approve Step --}}
                                        <div class="flex flex-col items-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $submission->status === 'approved' || $submission->status === 'published' ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                                                <i class="fas fa-check text-xs"></i>
                                            </div>
                                            <span class="text-xs mt-1">Approve</span>
                                        </div>

                                        {{-- Arrow --}}
                                        <div class="w-4 h-0.5 bg-gray-300"></div>

                                        {{-- Publish Step --}}
                                        <div class="flex flex-col items-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $submission->status === 'published' ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                                                <i class="fas fa-rocket text-xs"></i>
                                            </div>
                                            <span class="text-xs mt-1">Publish</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Payment Status --}}
                            <td class="px-4 py-4 text-center">
                                @if($submission->payment)
                                    <div class="flex flex-col items-center space-y-1">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium
                                            @if($submission->payment->status === 'confirmed') bg-green-100 text-green-800
                                            @elseif($submission->payment->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($submission->payment->status === 'rejected') bg-red-100 text-red-800
                                            @endif">
                                            {{ ucfirst($submission->payment->status) }}
                                        </span>
                                        <span class="text-xs text-gray-600">
                                            Rp {{ number_format($submission->payment->amount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
                                        Belum Bayar
                                    </span>
                                @endif
                            </td>

                            {{-- Quick Actions --}}
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center space-x-2">
                                    {{-- Payment Actions --}}
                                    @if($submission->payment && $submission->payment->status === 'pending')
                                        <button onclick="confirmPayment({{ $submission->payment->id }})" 
                                                class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs transition-colors">
                                            <i class="fas fa-check mr-1"></i>
                                            Konfirmasi
                                        </button>
                                        <button onclick="showRejectModal({{ $submission->payment->id }})" 
                                                class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs transition-colors">
                                            <i class="fas fa-times mr-1"></i>
                                            Tolak
                                        </button>
                                    @endif

                                    {{-- Submission Actions --}}
                                    @if($submission->status === 'pending_approval')
                                        <button onclick="approveSubmission({{ $submission->id }})" 
                                                class="bg-purple-500 hover:bg-purple-600 text-white px-2 py-1 rounded text-xs transition-colors">
                                            <i class="fas fa-thumbs-up mr-1"></i>
                                            Setujui
                                        </button>
                                    @elseif($submission->status === 'approved')
                                        <button onclick="publishSubmission({{ $submission->id }})" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs transition-colors">
                                            <i class="fas fa-rocket mr-1"></i>
                                            Publikasi
                                        </button>
                                    @endif

                                    {{-- Detail Button --}}
                                    <a href="{{ route('submissions.show', $submission) }}" 
                                       class="bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Belum ada submission yang perlu diproses</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $submissions->links() }}
        </div>
    </div>
</div>

{{-- Reject Payment Modal --}}
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Tolak Pembayaran</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Alasan Penolakan
                </label>
                <textarea name="rejection_reason" id="rejection_reason" rows="4" 
                          class="w-full border border-gray-300 rounded-lg p-3 text-sm" 
                          placeholder="Jelaskan alasan penolakan pembayaran..."
                          required></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="hideRejectModal()" 
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                    Tolak Pembayaran
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter functions
function filterByStatus() {
    const status = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('.submission-row');
    
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterByCategory() {
    const category = document.getElementById('filterCategory').value;
    const rows = document.querySelectorAll('.submission-row');
    
    rows.forEach(row => {
        if (category === 'all' || row.dataset.category === category) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Action functions
function confirmPayment(paymentId) {
    if (confirm('Konfirmasi pembayaran ini?')) {
        fetch(`/admin/payments/${paymentId}/confirm`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal mengkonfirmasi pembayaran');
            }
        });
    }
}

function approveSubmission(submissionId) {
    if (confirm('Setujui submission ini?')) {
        fetch(`/admin/submissions/${submissionId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menyetujui submission');
            }
        });
    }
}

function publishSubmission(submissionId) {
    if (confirm('Publikasikan submission ini ke komunitas?')) {
        fetch(`/admin/submissions/${submissionId}/publish`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal mempublikasi submission');
            }
        });
    }
}

function showRejectModal(paymentId) {
    document.getElementById('rejectForm').action = `/admin/payments/${paymentId}/reject`;
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}

function refreshDashboard() {
    location.reload();
}
</script>
@endsection