@extends('layouts.app')

@section('content')
<div class="main-card profile-card w-full max-w-6xl p-8 flex flex-wrap lg:flex-nowrap gap-8 bg-orange-100 text-gray-800">
    <!-- Bagian Kiri: Your Interests -->
    <div class="w-full lg:w-1/2 p-6 bg-orange-200 rounded-lg shadow-inner">
        <h3 class="text-xl font-bold mb-6 text-orange-700">Your Interests <span class="text-sm text-gray-600 font-normal">(Pilih maksimal 3)</span></h3>
        <div class="flex flex-wrap gap-3" id="interests-container">
            @php
                // Data interests yang diambil dari UI/UX Anda
                $interests = [
                    'Photography' => 'fas fa-camera', 'Shopping' => 'fas fa-shopping-bag', 'Karaoke' => 'fas fa-microphone',
                    'Yoga' => 'fas fa-leaf', 'Cooking' => 'fas fa-utensils', 'Tennis' => 'fas fa-tennis-ball',
                    'Run' => 'fas fa-running', 'Art' => 'fas fa-palette', 'Traveling' => 'fas fa-plane-departure',
                    'Extreme' => 'fas fa-mountain', 'Music' => 'fas fa-music', 'Drink' => 'fas fa-wine-glass',
                    'Video games' => 'fas fa-gamepad',
                ];
            @endphp

            @foreach ($interests as $name => $iconClass)
                <button type="button" class="btn-outline px-4 py-2 rounded-full flex items-center space-x-2 text-orange-700 border-orange-400 hover:bg-orange-700 hover:text-white transition-colors" data-interest="{{ strtolower(str_replace(' ', '_', $name)) }}">
                    <i class="{{ $iconClass }}"></i>
                    <span>{{ $name }}</span>
                </button>
            @endforeach
        </div>
        @error('interests')
            <p class="text-red-500 text-xs mt-3">{{ $message }}</p>
        @enderror
    </div>

    <!-- Bagian Kanan: Profile Details & Deskripsi -->
    <div class="w-full lg:w-1/2 p-6">
        <h3 class="text-xl font-bold mb-6 text-orange-700">Profile</h3>
        
        @php
            $isEditMode = request()->has('edit') || request()->routeIs('profile.edit');
            $actionRoute = $isEditMode ? route('profile.update') : route('profile.store');
            $methodType = $isEditMode ? 'PUT' : 'POST';
        @endphp
        
        <form method="POST" action="{{ $actionRoute }}">
            @csrf
            @if($isEditMode)
                @method('PUT')
            @endif
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <input type="text" id="username" name="username" placeholder="Username" 
                           class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 cursor-not-allowed" 
                           value="{{ Auth::user()->username ?? '' }}" readonly>
                    <p class="text-xs text-gray-500 mt-1">Username tidak dapat diubah</p>
                </div>
                
                <div>
                    <input type="text" id="full_name" name="full_name" placeholder="Full Name" 
                           class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('full_name') border-red-500 @enderror" 
                           value="{{ old('full_name', Auth::user()->full_name) }}" required>
                    @error('full_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <input type="text" id="prodi" name="prodi" placeholder="Prodi" 
                           class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('prodi') border-red-500 @enderror" 
                           value="{{ old('prodi', Auth::user()->prodi) }}" required>
                    @error('prodi')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <input type="text" id="fakultas" name="fakultas" placeholder="Fakultas" 
                           class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('fakultas') border-red-500 @enderror" 
                           value="{{ old('fakultas', Auth::user()->fakultas) }}" required>
                    @error('fakultas')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="md:col-span-2">
                    <select id="gender" name="gender" 
                            class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 @error('gender') border-red-500 @enderror" required>
                        <option value="">Select Gender</option>
                        <option value="Laki-laki" {{ old('gender', Auth::user()->gender) == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender', Auth::user()->gender) == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    @error('gender')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <h3 class="text-xl font-bold mb-4 text-orange-700">Deskripsi</h3>
            <div class="mb-6">
                <textarea id="description" name="description" placeholder="Ceritakan tentang diri Anda..." 
                          class="input-field-orange w-full h-32 bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 resize-none @error('description') border-red-500 @enderror" 
                          maxlength="1000">{{ old('description', Auth::user()->description) }}</textarea>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>Optional</span>
                    <span class="character-count">0/1000 characters</span>
                </div>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- ✅ TAMBAHAN: Social Media Links Section -->
            <h3 class="text-xl font-bold mb-4 text-orange-700 flex items-center">
                <i class="fas fa-share-alt mr-2"></i>
                Social Media Links
                <span class="text-sm text-gray-600 font-normal ml-2">(Optional)</span>
            </h3>
            
            <div class="space-y-4 mb-6" id="social-media-section">
                <!-- LinkedIn -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-blue-600 rounded-lg flex-shrink-0">
                        <i class="fab fa-linkedin-in text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <input type="url" 
                               id="linkedin_url" 
                               name="linkedin_url" 
                               placeholder="https://linkedin.com/in/your-profile" 
                               class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('linkedin_url') border-red-500 @enderror social-url-input"
                               data-platform="linkedin"
                               value="{{ old('linkedin_url', Auth::user()->social_links['linkedin'] ?? '') }}">
                        @error('linkedin_url')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <div class="validation-message"></div>
                    </div>
                </div>

                <!-- GitHub -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-gray-800 rounded-lg flex-shrink-0">
                        <i class="fab fa-github text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <input type="url" 
                               id="github_url" 
                               name="github_url" 
                               placeholder="https://github.com/your-username" 
                               class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('github_url') border-red-500 @enderror social-url-input"
                               data-platform="github"
                               value="{{ old('github_url', Auth::user()->social_links['github'] ?? '') }}">
                        @error('github_url')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <div class="validation-message"></div>
                    </div>
                </div>

                <!-- Instagram -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-pink-600 rounded-lg flex-shrink-0">
                        <i class="fab fa-instagram text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <input type="url" 
                               id="instagram_url" 
                               name="instagram_url" 
                               placeholder="https://instagram.com/your-username" 
                               class="input-field-orange w-full bg-orange-50 border-orange-300 text-gray-800 placeholder-gray-500 @error('instagram_url') border-red-500 @enderror social-url-input"
                               data-platform="instagram"
                               value="{{ old('instagram_url', Auth::user()->social_links['instagram'] ?? '') }}">
                        @error('instagram_url')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <div class="validation-message"></div>
                    </div>
                </div>
            </div>

            <!-- Tips section for social media -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3 flex-shrink-0"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Tips untuk social media links:</p>
                        <ul class="list-disc list-inside text-blue-700 space-y-1">
                            <li>Gunakan URL lengkap termasuk https://</li>
                            <li>Pastikan profil Anda public atau dapat diakses</li>
                            <li>Link ini akan terlihat oleh pengguna lain</li>
                        </ul>
                    </div>
                </div>
            </div>

            <input type="hidden" name="interests" id="interests-hidden-input" value="{{ old('interests', Auth::user()->interests ? json_encode(Auth::user()->interests) : '[]') }}">

            <div class="mt-8 text-center">
                <button type="submit" class="btn-primary w-full max-w-xs bg-orange-600 hover:bg-orange-700 transition-all duration-300" id="submit-button">
                    @if($isEditMode)
                        <i class="fas fa-save mr-2"></i>Update Profile
                    @else
                        <i class="fas fa-save mr-2"></i>Save Profile
                    @endif
                </button>
                
                @if($isEditMode)
                    <div class="mt-3">
                        <a href="{{ route('user.profile') }}" 
                           class="text-gray-600 hover:text-gray-800 text-sm transition-colors">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ===============================================
        // ✅ EXISTING INTERESTS FUNCTIONALITY (ENHANCED)
        // ===============================================
        const interestsContainer = document.getElementById('interests-container');
        const interestButtons = interestsContainer.querySelectorAll('button[data-interest]');
        const interestsHiddenInput = document.getElementById('interests-hidden-input');
        const MAX_INTERESTS = 3;

        let selectedInterests = [];

        // Inisialisasi: Tandai minat yang sudah tersimpan di database atau dari old() input
        let initialInterests = [];
        try {
            if (interestsHiddenInput.value) {
                const parsedValue = JSON.parse(interestsHiddenInput.value);
                if (Array.isArray(parsedValue)) {
                    initialInterests = parsedValue;
                }
            }
        } catch (e) {
            console.error("Error parsing initial interests:", e);
        }

        selectedInterests = initialInterests;

        // Enhanced update function
        function updateInterestsUI() {
            interestButtons.forEach(button => {
                const interest = button.getAttribute('data-interest');
                const isSelected = selectedInterests.includes(interest);
                
                if (isSelected) {
                    button.classList.add('selected', 'bg-orange-700', 'text-white');
                    button.classList.remove('text-orange-700');
                } else {
                    button.classList.remove('selected', 'bg-orange-700', 'text-white');
                    button.classList.add('text-orange-700');
                }
            });

            updateButtonStates();
            interestsHiddenInput.value = JSON.stringify(selectedInterests);
        }

        // Fungsi untuk mengelola status disable tombol ketika batas tercapai
        function updateButtonStates() {
            if (selectedInterests.length >= MAX_INTERESTS) {
                interestButtons.forEach(button => {
                    if (!button.classList.contains('selected')) {
                        button.disabled = true;
                        button.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                });
            } else {
                interestButtons.forEach(button => {
                    button.disabled = false;
                    button.classList.remove('opacity-50', 'cursor-not-allowed');
                });
            }
        }

        // Listener untuk klik tombol minat
        interestButtons.forEach(button => {
            button.addEventListener('click', function () {
                const interest = this.dataset.interest;
                const isSelected = this.classList.contains('selected');

                if (isSelected) {
                    // Jika sudah dipilih, hapus dari daftar
                    selectedInterests = selectedInterests.filter(item => item !== interest);
                } else {
                    // Jika belum dipilih dan kuota masih ada, tambahkan ke daftar
                    if (selectedInterests.length < MAX_INTERESTS) {
                        selectedInterests.push(interest);
                    } else {
                        // Beri tahu pengguna bahwa batas sudah tercapai
                        showNotification(`Anda hanya dapat memilih maksimal ${MAX_INTERESTS} minat.`, 'warning');
                        return;
                    }
                }
                
                updateInterestsUI();
            });
        });

        // Initialize
        updateInterestsUI();

        // ===============================================
        // ✅ NEW: CHARACTER COUNTER FUNCTIONALITY
        // ===============================================
        const descriptionTextarea = document.getElementById('description');
        const characterCount = document.querySelector('.character-count');
        
        if (descriptionTextarea && characterCount) {
            function updateCharacterCount() {
                const length = descriptionTextarea.value.length;
                characterCount.textContent = `${length}/1000 characters`;
                
                if (length > 900) {
                    characterCount.classList.add('text-red-500');
                    characterCount.classList.remove('text-yellow-500', 'text-gray-500');
                } else if (length > 750) {
                    characterCount.classList.add('text-yellow-500');
                    characterCount.classList.remove('text-red-500', 'text-gray-500');
                } else {
                    characterCount.classList.add('text-gray-500');
                    characterCount.classList.remove('text-red-500', 'text-yellow-500');
                }
            }
            
            descriptionTextarea.addEventListener('input', updateCharacterCount);
            updateCharacterCount(); // Initialize
        }

        // ===============================================
        // ✅ NEW: SOCIAL MEDIA URL VALIDATION
        // ===============================================
        const socialInputs = document.querySelectorAll('.social-url-input');
        
        socialInputs.forEach(input => {
            const platform = input.dataset.platform;
            let timeout;
            
            // Auto-format URL on blur
            input.addEventListener('blur', (e) => {
                if (e.target.value && !e.target.value.startsWith('http')) {
                    e.target.value = 'https://' + e.target.value;
                }
                validateSocialUrl(e.target, platform);
            });

            // Real-time validation with debounce
            input.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    validateSocialUrl(e.target, platform);
                }, 750);
            });

            // Handle paste events
            input.addEventListener('paste', (e) => {
                setTimeout(() => {
                    if (input.value && !input.value.startsWith('http')) {
                        input.value = 'https://' + input.value;
                    }
                    validateSocialUrl(input, platform);
                }, 100);
            });
        });

        // ✅ Validate social media URL function
        async function validateSocialUrl(input, platform) {
            const validationMessage = input.parentNode.querySelector('.validation-message');
            
            if (!input.value.trim()) {
                clearValidationMessage(validationMessage);
                removeInputValidationClasses(input);
                return;
            }

            // Show loading state
            showValidationMessage(validationMessage, 'Checking...', 'loading');
            
            try {
                const response = await fetch('/profile/validate-social-url', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        url: input.value,
                        platform: platform
                    })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                
                if (data.valid) {
                    showValidationMessage(validationMessage, 'Valid URL ✓', 'success');
                    setInputValidationClass(input, 'valid');
                    
                    // Update input value if formatted
                    if (data.formatted_url && data.formatted_url !== input.value) {
                        input.value = data.formatted_url;
                    }
                } else {
                    showValidationMessage(validationMessage, data.message || 'Invalid URL', 'error');
                    setInputValidationClass(input, 'invalid');
                }
            } catch (error) {
                console.error('Validation error:', error);
                showValidationMessage(validationMessage, 'Unable to validate URL', 'error');
                setInputValidationClass(input, 'invalid');
            }
        }

        // ✅ Validation message functions
        function showValidationMessage(element, message, type) {
            if (!element) return;
            
            const classes = {
                success: 'text-green-600',
                error: 'text-red-600',
                loading: 'text-blue-600'
            };
            
            element.className = `validation-message text-xs mt-1 ${classes[type] || 'text-gray-600'}`;
            element.textContent = message;
        }

        function clearValidationMessage(element) {
            if (!element) return;
            element.className = 'validation-message';
            element.textContent = '';
        }

        function setInputValidationClass(input, state) {
            input.classList.remove('border-green-500', 'border-red-500');
            if (state === 'valid') {
                input.classList.add('border-green-500');
            } else if (state === 'invalid') {
                input.classList.add('border-red-500');
            }
        }

        function removeInputValidationClasses(input) {
            input.classList.remove('border-green-500', 'border-red-500');
        }

        // ===============================================
        // ✅ NEW: FORM ENHANCEMENT
        // ===============================================
        const form = document.querySelector('form');
        const submitButton = document.getElementById('submit-button');
        const originalButtonText = submitButton.innerHTML;
        
        // Add loading state to submit button
        form.addEventListener('submit', function(e) {
            // Validate that at least one interest is selected
            if (selectedInterests.length === 0) {
                e.preventDefault();
                showNotification('Pilih minimal satu minat sebelum melanjutkan.', 'error');
                return;
            }
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            
            // Reset after 10 seconds as fallback
            setTimeout(() => {
                resetSubmitButton();
            }, 10000);
        });

        function resetSubmitButton() {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        }

        // Reset button if form submission fails (page reload)
        window.addEventListener('pageshow', function() {
            resetSubmitButton();
        });

        // ===============================================
        // ✅ NEW: NOTIFICATION SYSTEM
        // ===============================================
        function showNotification(message, type = 'info', duration = 4000) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.profile-notification');
            existingNotifications.forEach(notification => notification.remove());
            
            const notification = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };
            
            notification.className = `profile-notification fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${getNotificationIcon(type)} mr-2"></i>
                    <span>${message}</span>
                    <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, duration);
        }

        function getNotificationIcon(type) {
            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                info: 'info-circle',
                warning: 'exclamation-triangle'
            };
            return icons[type] || 'info-circle';
        }

        // ===============================================
        // ✅ NEW: ENHANCED INPUT INTERACTIONS
        // ===============================================
        
        // Add focus/blur effects to inputs
        const allInputs = document.querySelectorAll('input, textarea, select');
        allInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.classList.add('ring-2', 'ring-orange-300');
            });
            
            input.addEventListener('blur', function() {
                this.classList.remove('ring-2', 'ring-orange-300');
            });
        });

        // Auto-resize textarea
        if (descriptionTextarea) {
            descriptionTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });
        }

        // ===============================================
        // ✅ SUCCESS MESSAGE HANDLING
        // ===============================================
        @if(session('success'))
            showNotification('{{ session('success') }}', 'success');
        @endif

        @if(session('error'))
            showNotification('{{ session('error') }}', 'error');
        @endif

        @if($errors->any())
            showNotification('Please check the form for errors.', 'error');
        @endif
    });
</script>
@endpush

<style>
    .transition-colors {
        transition: all 0.3s ease;
    }
    
    .social-url-input:focus {
        outline: none;
    }
    
    .validation-message {
        min-height: 1rem;
        transition: all 0.2s ease;
    }
    
    .btn-outline.selected {
        background-color: #c2410c !important;
        color: white !important;
    }
    
    .profile-notification {
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    
    input:focus, textarea:focus, select:focus {
        border-color: #fb923c;
        box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.1);
    }
    
    .flex-shrink-0 {
        flex-shrink: 0;
    }
    
    @media (max-width: 768px) {
        .main-card {
            flex-direction: column;
            padding: 1rem;
        }
        
        #social-media-section .flex {
            flex-direction: column;
            align-items: stretch;
        }
        
        #social-media-section .flex-shrink-0 {
            align-self: flex-start;
            margin-bottom: 0.5rem;
        }
    }
</style>
@endsection