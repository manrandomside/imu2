@extends('layouts.app')

@section('content')
<div class="main-card w-full max-w-4xl bg-white rounded-lg shadow-xl overflow-hidden text-gray-800 p-0">
    
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-yellow-50 to-orange-50">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full bg-yellow-500 flex items-center justify-center">
                <i class="fas fa-edit text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Edit Konten</h1>
                <p class="text-gray-600">Perbaiki konten Anda sebelum submit ulang</p>
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

    {{-- Status Info --}}
    @if($submission->status === 'rejected' && $submission->rejection_reason)
        <div class="m-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center space-x-2 mb-2">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
                <h3 class="font-semibold text-red-800">Alasan Penolakan</h3>
            </div>
            <p class="text-red-700 text-sm">{{ $submission->rejection_reason }}</p>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('submissions.update', $submission) }}" enctype="multipart/form-data" class="p-6">
        @csrf
        @method('PUT')
        
        {{-- Category Selection --}}
        <div class="mb-6">
            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                Kategori Konten <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($categories as $category)
                    <div class="relative">
                        <input type="radio" 
                               id="category_{{ $category->id }}" 
                               name="category_id" 
                               value="{{ $category->id }}"
                               class="peer hidden"
                               {{ old('category_id', $submission->category_id) == $category->id ? 'checked' : '' }}>
                        <label for="category_{{ $category->id }}" 
                               class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                            <div class="flex items-center space-x-3">
                                <div class="text-2xl">{{ $category->icon }}</div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800">{{ $category->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $category->description }}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-lg font-bold text-blue-600">{{ $category->formatted_price }}</span>
                                        <span class="text-xs text-gray-500">Max: {{ $category->formatted_max_file_size }}</span>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>
            @error('category_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Title --}}
        <div class="mb-6">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                Judul Konten <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   id="title" 
                   name="title" 
                   value="{{ old('title', $submission->title) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 @error('title') border-red-500 @enderror"
                   placeholder="Masukkan judul yang menarik..."
                   maxlength="255">
            <div class="flex justify-between items-center mt-1">
                @error('title')
                    <p class="text-red-500 text-xs">{{ $message }}</p>
                @else
                    <span></span>
                @enderror
                <span id="title-counter" class="text-xs text-gray-500">0/255</span>
            </div>
        </div>

        {{-- Description --}}
        <div class="mb-6">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                Deskripsi Konten <span class="text-red-500">*</span>
            </label>
            <textarea id="description" 
                      name="description" 
                      rows="8"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none @error('description') border-red-500 @enderror"
                      placeholder="Jelaskan detail konten Anda (minimal 50 karakter)..."
                      maxlength="5000">{{ old('description', $submission->description) }}</textarea>
            <div class="flex justify-between items-center mt-1">
                @error('description')
                    <p class="text-red-500 text-xs">{{ $message }}</p>
                @else
                    <span class="text-xs text-gray-600">Minimal 50 karakter</span>
                @enderror
                <span id="description-counter" class="text-xs text-gray-500">0/5000</span>
            </div>
        </div>

        {{-- Current Attachment --}}
        @if($submission->hasAttachment())
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Lampiran Saat Ini</label>
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="{{ $submission->attachment_icon }} {{ $submission->attachment_color }} text-xl"></i>
                            <div>
                                <p class="font-medium text-gray-800">{{ $submission->attachment_name }}</p>
                                <p class="text-sm text-gray-600">{{ $submission->formatted_file_size }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('submissions.download', $submission) }}" 
                               class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm transition-colors">
                                <i class="fas fa-download mr-1"></i>
                                Download
                            </a>
                            <label class="flex items-center">
                                <input type="checkbox" name="remove_attachment" value="1" class="mr-2">
                                <span class="text-sm text-red-600">Hapus file</span>
                            </label>
                        </div>
                    </div>
                    
                    {{-- Image Preview --}}
                    @if($submission->isImageAttachment())
                        <div class="mt-3">
                            <img src="{{ $submission->attachment_url }}" 
                                 alt="Current Attachment" 
                                 class="max-w-full h-auto rounded border max-h-64 object-contain">
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- New File Attachment --}}
        <div class="mb-6">
            <label for="attachment" class="block text-sm font-medium text-gray-700 mb-2">
                {{ $submission->hasAttachment() ? 'Ganti Lampiran File (Opsional)' : 'Lampiran File (Opsional)' }}
            </label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                <input type="file" 
                       id="attachment" 
                       name="attachment" 
                       class="hidden"
                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                <div id="file-drop-area" onclick="document.getElementById('attachment').click()" class="cursor-pointer">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600 mb-1">Klik untuk memilih file baru atau drag & drop</p>
                    <p class="text-sm text-gray-500">JPG, PNG, PDF, DOC, XLS - Max 10MB</p>
                </div>
                <div id="file-preview" class="mt-4 hidden">
                    <div class="flex items-center justify-center space-x-3 p-3 bg-gray-50 rounded">
                        <i id="file-icon" class="fas fa-file text-blue-500"></i>
                        <span id="file-name" class="text-sm text-gray-700"></span>
                        <span id="file-size" class="text-xs text-gray-500"></span>
                        <button type="button" onclick="clearFileInput()" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            @error('attachment')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Information Box --}}
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-yellow-500 mt-1"></i>
                <div class="text-sm text-yellow-800">
                    <h4 class="font-semibold mb-2">Informasi Edit:</h4>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Setelah diedit, status akan kembali ke <strong>"Menunggu Pembayaran"</strong></li>
                        <li>Anda perlu melakukan pembayaran ulang jika diperlukan</li>
                        <li>Pastikan semua informasi sudah benar sebelum submit</li>
                        <li>Edit hanya bisa dilakukan untuk konten yang belum disetujui</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('submissions.show', $submission) }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Batal
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors">
                <i class="fas fa-save mr-2"></i>
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counters
    const titleInput = document.getElementById('title');
    const titleCounter = document.getElementById('title-counter');
    const descriptionInput = document.getElementById('description');
    const descriptionCounter = document.getElementById('description-counter');
    const fileInput = document.getElementById('attachment');
    const filePreview = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const fileIcon = document.getElementById('file-icon');
    const fileDropArea = document.getElementById('file-drop-area');

    // Title counter
    titleInput.addEventListener('input', function() {
        const length = this.value.length;
        titleCounter.textContent = `${length}/255`;
        if (length > 240) {
            titleCounter.classList.add('text-red-500');
        } else {
            titleCounter.classList.remove('text-red-500');
        }
    });

    // Description counter
    descriptionInput.addEventListener('input', function() {
        const length = this.value.length;
        descriptionCounter.textContent = `${length}/5000`;
        if (length < 50) {
            descriptionCounter.classList.add('text-red-500');
            descriptionCounter.classList.remove('text-green-500');
        } else if (length > 4500) {
            descriptionCounter.classList.add('text-red-500');
            descriptionCounter.classList.remove('text-green-500');
        } else {
            descriptionCounter.classList.add('text-green-500');
            descriptionCounter.classList.remove('text-red-500');
        }
    });

    // File input handler
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            showFilePreview(file);
        }
    });

    // Drag and drop
    fileDropArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-blue-400', 'bg-blue-50');
    });

    fileDropArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-blue-400', 'bg-blue-50');
    });

    fileDropArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFilePreview(files[0]);
        }
    });

    function showFilePreview(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        
        // Update icon based on file type
        const ext = file.name.split('.').pop().toLowerCase();
        switch(ext) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                fileIcon.className = 'fas fa-image text-green-500';
                break;
            case 'pdf':
                fileIcon.className = 'fas fa-file-pdf text-red-500';
                break;
            case 'doc':
            case 'docx':
                fileIcon.className = 'fas fa-file-word text-blue-500';
                break;
            case 'xls':
            case 'xlsx':
                fileIcon.className = 'fas fa-file-excel text-green-600';
                break;
            default:
                fileIcon.className = 'fas fa-file text-gray-500';
        }
        
        filePreview.classList.remove('hidden');
    }

    window.clearFileInput = function() {
        fileInput.value = '';
        filePreview.classList.add('hidden');
    };

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Initialize counters
    titleInput.dispatchEvent(new Event('input'));
    descriptionInput.dispatchEvent(new Event('input'));
});
</script>
@endpush

@endsection