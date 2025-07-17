{{-- resources/views/admin/submissions/edit.blade.php --}}
@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-edit mr-3 text-blue-600"></i>
                Edit Submission
            </h1>
            <p class="text-gray-600">Edit konten submission yang telah disubmit oleh user</p>
        </div>
        
        <div class="flex space-x-2">
            <a href="{{ route('admin.submissions.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-1"></i>
                Kembali
            </a>
            <a href="{{ route('submissions.show', $submission) }}" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg" target="_blank">
                <i class="fas fa-eye mr-1"></i>
                Preview
            </a>
        </div>
    </div>

    <!-- Submission Info Card -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Informasi Submission</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-purple-800">
                                {{ substr($submission->user->full_name, 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $submission->user->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $submission->user->email }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Saat Ini</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $submission->status_badge_color }}">
                        @switch($submission->status)
                            @case('pending_payment')
                                <i class="fas fa-credit-card mr-1"></i>
                                Menunggu Pembayaran
                                @break
                            @case('pending_approval')
                                <i class="fas fa-clock mr-1"></i>
                                Perlu Review
                                @break
                            @case('approved')
                                <i class="fas fa-check mr-1"></i>
                                Siap Publish
                                @break
                            @case('published')
                                <i class="fas fa-rocket mr-1"></i>
                                Dipublikasi
                                @break
                            @case('rejected')
                                <i class="fas fa-times mr-1"></i>
                                Ditolak
                                @break
                        @endswitch
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Dibuat</label>
                    <p class="text-sm text-gray-900">{{ $submission->created_at->format('d M Y, H:i') }}</p>
                </div>
            </div>

            @if($submission->payment)
                <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-green-800">Payment Information</h4>
                            <p class="text-sm text-green-700">
                                {{ $submission->payment->payment_method }} - 
                                Rp {{ number_format($submission->payment->amount, 0, ',', '.') }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                            {{ $submission->payment->status === 'confirmed' ? 'bg-green-100 text-green-800' : 
                               ($submission->payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ $submission->payment->status === 'confirmed' ? 'Dikonfirmasi' : 
                               ($submission->payment->status === 'pending' ? 'Pending' : 'Ditolak') }}
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Form -->
    <form method="POST" action="{{ route('admin.submissions.admin_update', $submission) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Edit Konten</h2>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Judul <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" 
                           value="{{ old('title', $submission->title) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('title') border-red-500 @enderror"
                           placeholder="Masukkan judul konten">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Kategori <span class="text-red-500">*</span>
                    </label>
                    <select id="category_id" name="category_id" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('category_id') border-red-500 @enderror">
                        <option value="">Pilih Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                    {{ old('category_id', $submission->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                                @if($category->price > 0)
                                    (Rp {{ number_format($category->price, 0, ',', '.') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi <span class="text-red-500">*</span>
                    </label>
                    <textarea id="description" name="description" rows="8" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                              placeholder="Masukkan deskripsi konten (minimal 50 karakter)">{{ old('description', $submission->description) }}</textarea>
                    <div class="mt-1 flex justify-between">
                        @error('description')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="text-sm text-gray-500">Minimal 50 karakter</p>
                        @enderror
                        <p class="text-sm text-gray-500">
                            <span id="char-count">{{ strlen(old('description', $submission->description)) }}</span>/5000
                        </p>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="pending_payment" {{ old('status', $submission->status) == 'pending_payment' ? 'selected' : '' }}>
                            Menunggu Pembayaran
                        </option>
                        <option value="pending_approval" {{ old('status', $submission->status) == 'pending_approval' ? 'selected' : '' }}>
                            Perlu Review
                        </option>
                        <option value="approved" {{ old('status', $submission->status) == 'approved' ? 'selected' : '' }}>
                            Siap Publish
                        </option>
                        <option value="published" {{ old('status', $submission->status) == 'published' ? 'selected' : '' }}>
                            Dipublikasi
                        </option>
                        <option value="rejected" {{ old('status', $submission->status) == 'rejected' ? 'selected' : '' }}>
                            Ditolak
                        </option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Rejection Reason (shown when status is rejected) -->
                <div id="rejection-reason-container" class="hidden">
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Alasan Penolakan
                    </label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('rejection_reason') border-red-500 @enderror"
                              placeholder="Masukkan alasan penolakan submission">{{ old('rejection_reason', $submission->rejection_reason) }}</textarea>
                    @error('rejection_reason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Current Attachment -->
                @if($submission->attachment_path)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lampiran Saat Ini</label>
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-file text-gray-400"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $submission->attachment_name }}</p>
                                        <p class="text-sm text-gray-500">{{ $submission->formatted_file_size }}</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="{{ $submission->attachment_url }}" target="_blank"
                                       class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-eye mr-1"></i>
                                        Lihat
                                    </a>
                                    <a href="{{ route('submissions.download', $submission) }}"
                                       class="text-green-600 hover:text-green-800 text-sm">
                                        <i class="fas fa-download mr-1"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- New Attachment -->
                <div>
                    <label for="attachment" class="block text-sm font-medium text-gray-700 mb-2">
                        @if($submission->attachment_path)
                            Ganti Lampiran (Opsional)
                        @else
                            Lampiran (Opsional)
                        @endif
                    </label>
                    <div class="flex items-center justify-center w-full">
                        <label for="attachment" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-3"></i>
                                <p class="mb-2 text-sm text-gray-500">
                                    <span class="font-semibold">Klik untuk upload</span> atau drag and drop
                                </p>
                                <p class="text-xs text-gray-500">PDF, DOC, DOCX, PPT, PPTX (MAX. 10MB)</p>
                            </div>
                            <input id="attachment" name="attachment" type="file" class="hidden" accept=".pdf,.doc,.docx,.ppt,.pptx">
                        </label>
                    </div>
                    @error('attachment')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div id="selected-file" class="mt-2 hidden">
                        <div class="flex items-center space-x-2 text-sm text-green-600">
                            <i class="fas fa-check-circle"></i>
                            <span id="file-name"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                <button type="button" onclick="history.back()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Batal
                </button>
                
                <div class="flex space-x-2">
                    <button type="button" onclick="previewSubmission()" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-eye mr-1"></i>
                        Preview
                    </button>
                    <button type="submit" 
                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-save mr-1"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const rejectionContainer = document.getElementById('rejection-reason-container');
    const descriptionTextarea = document.getElementById('description');
    const charCount = document.getElementById('char-count');
    const attachmentInput = document.getElementById('attachment');
    const selectedFileDiv = document.getElementById('selected-file');
    const fileNameSpan = document.getElementById('file-name');

    // Toggle rejection reason visibility
    function toggleRejectionReason() {
        if (statusSelect.value === 'rejected') {
            rejectionContainer.classList.remove('hidden');
        } else {
            rejectionContainer.classList.add('hidden');
        }
    }

    // Update character count
    function updateCharCount() {
        const count = descriptionTextarea.value.length;
        charCount.textContent = count;
        
        if (count > 5000) {
            charCount.parentElement.classList.add('text-red-500');
        } else if (count < 50) {
            charCount.parentElement.classList.add('text-yellow-500');
            charCount.parentElement.classList.remove('text-red-500');
        } else {
            charCount.parentElement.classList.remove('text-red-500', 'text-yellow-500');
        }
    }

    // Handle file selection
    function handleFileSelect() {
        const file = attachmentInput.files[0];
        if (file) {
            fileNameSpan.textContent = file.name;
            selectedFileDiv.classList.remove('hidden');
        } else {
            selectedFileDiv.classList.add('hidden');
        }
    }

    // Event listeners
    statusSelect.addEventListener('change', toggleRejectionReason);
    descriptionTextarea.addEventListener('input', updateCharCount);
    attachmentInput.addEventListener('change', handleFileSelect);

    // Initialize
    toggleRejectionReason();
    updateCharCount();
});

function previewSubmission() {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    
    // You can implement preview functionality here
    // For now, just show an alert
    alert('Preview functionality akan dibuka di tab baru');
    window.open('{{ route("submissions.show", $submission) }}', '_blank');
}
</script>
@endpush
@endsection