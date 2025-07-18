@extends('layouts.app')

@section('content')
{{-- Container utama untuk home dashboard --}}
<div class="w-full max-w-7xl mx-auto flex flex-col h-full bg-gray-50 rounded-lg shadow-xl text-gray-800">
    <div class="flex-grow overflow-y-auto p-8">

        {{-- ✨ HERO CAROUSEL SECTION --}}
        <div class="relative w-full h-96 mb-8 overflow-hidden rounded-2xl shadow-2xl" id="heroCarousel">
            {{-- Carousel Container --}}
            <div class="carousel-wrapper relative w-full h-full">
                
                {{-- Slide 1: Website Introduction --}}
                <div class="carousel-slide active absolute inset-0 bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 text-white">
                    <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                    {{-- Floating geometric shapes --}}
                    <div class="absolute top-8 left-8 w-16 h-16 bg-white bg-opacity-10 rounded-full animate-pulse"></div>
                    <div class="absolute bottom-12 right-12 w-8 h-8 bg-white bg-opacity-20 rounded-full animate-bounce" style="animation-delay: 1s;"></div>
                    <div class="absolute top-1/3 right-8 w-4 h-4 bg-white bg-opacity-15 rounded-full animate-ping"></div>
                    
                    <div class="relative z-10 h-full flex flex-col justify-center items-center text-center p-8">
                        <div class="mb-6 animate-bounce">
                            <div class="w-24 h-24 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white border-opacity-30">
                                <i class="fas fa-university text-4xl"></i>
                            </div>
                        </div>
                        <h2 class="text-3xl font-bold mb-4 leading-tight">
                            Platform Komunikasi Alumni & Mahasiswa
                        </h2>
                        <p class="text-lg text-blue-100 leading-relaxed max-w-md mx-auto mb-6">
                            IMU menghubungkan alumni dan mahasiswa aktif Universitas Udayana untuk berbagi pengalaman, networking, dan membangun komunitas yang kuat.
                        </p>
                        <div class="flex flex-wrap justify-center gap-3">
                            <div class="px-4 py-2 bg-white bg-opacity-20 rounded-full text-sm font-medium backdrop-blur-sm border border-white border-opacity-30">
                                <i class="fas fa-users mr-2"></i>Networking
                            </div>
                            <div class="px-4 py-2 bg-white bg-opacity-20 rounded-full text-sm font-medium backdrop-blur-sm border border-white border-opacity-30">
                                <i class="fas fa-handshake mr-2"></i>Komunitas
                            </div>
                            <div class="px-4 py-2 bg-white bg-opacity-20 rounded-full text-sm font-medium backdrop-blur-sm border border-white border-opacity-30">
                                <i class="fas fa-graduation-cap mr-2"></i>Alumni
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Slide 2: Find Feature --}}
                <div class="carousel-slide absolute inset-0 bg-gradient-to-br from-emerald-500 via-teal-600 to-blue-700 text-white">
                    <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                    {{-- Search animation elements --}}
                    <div class="absolute top-6 right-6 w-12 h-12 border-2 border-white border-opacity-30 rounded-full animate-spin-slow"></div>
                    <div class="absolute bottom-8 left-8 w-6 h-6 bg-white bg-opacity-20 rounded-full animate-pulse"></div>
                    
                    <div class="relative z-10 h-full flex flex-col justify-center items-center text-center p-8">
                        <div class="mb-6 relative">
                            <div class="w-24 h-24 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white border-opacity-30">
                                <i class="fas fa-search-plus text-4xl"></i>
                            </div>
                            {{-- Radar circles --}}
                            <div class="absolute inset-0 rounded-2xl border-2 border-white border-opacity-30 animate-ping"></div>
                            <div class="absolute inset-2 rounded-2xl border border-white border-opacity-20 animate-ping" style="animation-delay: 0.5s;"></div>
                        </div>
                        <h2 class="text-3xl font-bold mb-4 leading-tight">
                            Temukan Koneksi yang Tepat
                        </h2>
                        <p class="text-lg text-emerald-100 leading-relaxed max-w-md mx-auto mb-6">
                            Gunakan algoritma matching cerdas untuk menemukan alumni dan mahasiswa yang sesuai dengan minat, jurusan, dan tujuan networking Anda.
                        </p>
                        <a href="{{ route('find.people') }}" 
                           class="inline-flex items-center px-8 py-4 bg-white text-emerald-600 rounded-2xl font-bold hover:bg-emerald-50 transition-all transform hover:scale-105 hover:shadow-xl">
                            <i class="fas fa-rocket mr-3 text-lg"></i>
                            Mulai Pencarian
                        </a>
                    </div>
                </div>

                {{-- Slide 3: Community Feature --}}
                <div class="carousel-slide absolute inset-0 bg-gradient-to-br from-purple-600 via-pink-600 to-red-600 text-white">
                    <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                    {{-- Community connection lines --}}
                    <div class="absolute inset-0">
                        <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-white bg-opacity-40 rounded-full animate-pulse"></div>
                        <div class="absolute top-3/4 right-1/4 w-2 h-2 bg-white bg-opacity-40 rounded-full animate-pulse" style="animation-delay: 1s;"></div>
                        <div class="absolute top-1/2 left-1/6 w-2 h-2 bg-white bg-opacity-40 rounded-full animate-pulse" style="animation-delay: 2s;"></div>
                        <div class="absolute top-1/3 right-1/3 w-2 h-2 bg-white bg-opacity-40 rounded-full animate-pulse" style="animation-delay: 0.5s;"></div>
                    </div>
                    
                    <div class="relative z-10 h-full flex flex-col justify-center items-center text-center p-8">
                        <div class="mb-6 relative">
                            <div class="w-24 h-24 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white border-opacity-30">
                                <i class="fas fa-users text-4xl"></i>
                            </div>
                            {{-- Orbiting dots --}}
                            <div class="absolute -top-2 -right-2 w-4 h-4 bg-white bg-opacity-60 rounded-full animate-bounce"></div>
                            <div class="absolute -bottom-2 -left-2 w-3 h-3 bg-white bg-opacity-60 rounded-full animate-bounce" style="animation-delay: 0.5s;"></div>
                        </div>
                        <h2 class="text-3xl font-bold mb-4 leading-tight">
                            Bergabung dengan Komunitas
                        </h2>
                        <p class="text-lg text-purple-100 leading-relaxed max-w-md mx-auto mb-6">
                            Eksplorasi berbagai komunitas berdasarkan minat, hobi, atau bidang studi. Diskusi, berbagi knowledge, dan berkembang bersama.
                        </p>
                        <a href="{{ route('community') }}" 
                           class="inline-flex items-center px-8 py-4 bg-white text-purple-600 rounded-2xl font-bold hover:bg-purple-50 transition-all transform hover:scale-105 hover:shadow-xl">
                            <i class="fas fa-comments mr-3 text-lg"></i>
                            Gabung Sekarang
                        </a>
                    </div>
                </div>
            </div>

            {{-- Navigation Dots --}}
            <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex space-x-3 z-20">
                <button class="carousel-dot active w-4 h-4 rounded-full bg-white bg-opacity-80 transition-all duration-300 hover:scale-110" data-slide="0"></button>
                <button class="carousel-dot w-4 h-4 rounded-full bg-white bg-opacity-50 transition-all duration-300 hover:scale-110" data-slide="1"></button>
                <button class="carousel-dot w-4 h-4 rounded-full bg-white bg-opacity-50 transition-all duration-300 hover:scale-110" data-slide="2"></button>
            </div>

            {{-- Navigation Arrows --}}
            <button class="carousel-prev absolute left-6 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full flex items-center justify-center text-white transition-all duration-300 hover:scale-110 backdrop-blur-sm z-20 border border-white border-opacity-30">
                <i class="fas fa-chevron-left text-lg"></i>
            </button>
            <button class="carousel-next absolute right-6 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full flex items-center justify-center text-white transition-all duration-300 hover:scale-110 backdrop-blur-sm z-20 border border-white border-opacity-30">
                <i class="fas fa-chevron-right text-lg"></i>
            </button>

            {{-- Progress Bar --}}
            <div class="absolute bottom-0 left-0 h-1 bg-white bg-opacity-30 z-20" style="width: 100%;">
                <div class="carousel-progress h-full bg-white transition-all duration-100 ease-linear" style="width: 0%;"></div>
            </div>
        </div>

        {{-- Main Action Cards --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <a href="{{ route('find.people') }}" class="group bg-gradient-to-br from-emerald-400 to-emerald-600 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white bg-opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-search text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Find People</h3>
                    <p class="text-emerald-100 text-sm leading-relaxed mb-4">Temukan alumni dan mahasiswa yang sesuai dengan preferensi Anda</p>
                    <div class="flex items-center text-sm font-medium">
                        <span>Mulai Pencarian</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('community') }}" class="group bg-gradient-to-br from-purple-500 to-pink-600 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white bg-opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Community</h3>
                    <p class="text-purple-100 text-sm leading-relaxed mb-4">Bergabung dengan komunitas yang sesuai minat dan bidang studi</p>
                    <div class="flex items-center text-sm font-medium">
                        <span>Eksplorasi</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('submissions.index') }}" class="group bg-gradient-to-br from-blue-500 to-indigo-600 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white bg-opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-plus-circle text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Submit Content</h3>
                    <p class="text-blue-100 text-sm leading-relaxed mb-4">Bagikan konten, artikel, atau karya Anda dengan komunitas</p>
                    <div class="flex items-center text-sm font-medium">
                        <span>Mulai Berbagi</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
        </div>



        {{-- Call to Action --}}
        <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-12 rounded-2xl text-white text-center shadow-2xl relative overflow-hidden">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute top-0 left-0 w-32 h-32 bg-white bg-opacity-10 rounded-full -ml-16 -mt-16 animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-24 h-24 bg-white bg-opacity-10 rounded-full -mr-12 -mb-12 animate-pulse" style="animation-delay: 1s;"></div>
            
            <div class="relative z-10">
                <h3 class="text-3xl font-bold mb-4">Siap Untuk Memulai Networking? 🚀</h3>
                <p class="text-indigo-100 mb-8 max-w-2xl mx-auto text-lg">
                    Bergabunglah dengan ribuan alumni dan mahasiswa Udayana. Bangun koneksi yang bermakna dan kembangkan karir Anda.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="{{ route('find.people') }}" class="bg-white text-indigo-600 px-8 py-4 rounded-xl font-bold hover:bg-indigo-50 transition-all transform hover:scale-105 text-lg">
                        <i class="fas fa-search mr-2"></i>
                        Mulai Networking
                    </a>
                    <a href="{{ route('user.profile') }}" class="border-2 border-white text-white px-8 py-4 rounded-xl font-bold hover:bg-white hover:text-indigo-600 transition-all text-lg">
                        <i class="fas fa-user mr-2"></i>
                        Lengkapi Profile
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Enhanced CSS & JavaScript --}}
<style>
/* ================================
   Advanced Carousel & Component Styles
   ================================ */
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin-slow {
    animation: spin-slow 10s linear infinite;
}

/* Carousel Slides */
.carousel-slide {
    opacity: 0;
    transform: translateX(100%) scale(0.95);
    transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    visibility: hidden;
}

.carousel-slide.active {
    opacity: 1;
    transform: translateX(0) scale(1);
    visibility: visible;
}

.carousel-slide.prev {
    transform: translateX(-100%) scale(0.95);
}

/* Enhanced Floating Elements */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.floating {
    animation: float 6s ease-in-out infinite;
}

/* Navigation Dots Enhanced */
.carousel-dot {
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
}

.carousel-dot:hover {
    background-color: rgba(255, 255, 255, 0.8) !important;
    transform: scale(1.2);
}

.carousel-dot.active {
    background-color: rgba(255, 255, 255, 0.95) !important;
    transform: scale(1.3);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Progress Bar Animation */
.carousel-progress {
    transition: width 0.1s linear;
}

/* Enhanced Card Hover Effects */
.group:hover {
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

/* Advanced Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Glassmorphism Effect */
.glass {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .carousel-slide {
        padding: 1.5rem;
    }
    
    .carousel-slide h2 {
        font-size: 1.5rem;
        line-height: 1.3;
    }
    
    .carousel-slide p {
        font-size: 0.9rem;
    }
    
    .carousel-prev,
    .carousel-next {
        width: 40px;
        height: 40px;
    }
    
    #heroCarousel {
        height: 350px;
    }

    .grid.grid-cols-1.lg\\:grid-cols-3 {
        grid-template-columns: 1fr;
    }
}

/* Loading Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeInUp 0.8s ease-out;
}

/* Staggered animations */
.fade-in:nth-child(1) { animation-delay: 0.1s; }
.fade-in:nth-child(2) { animation-delay: 0.2s; }
.fade-in:nth-child(3) { animation-delay: 0.3s; }
.fade-in:nth-child(4) { animation-delay: 0.4s; }

/* Custom scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    const progressBar = document.querySelector('.carousel-progress');
    
    let currentSlide = 0;
    const totalSlides = slides.length;
    let autoSlideInterval;
    let progressInterval;
    const slideDuration = 7000; // 7 seconds
    
    // Add fade-in animation to components
    document.querySelectorAll('.bg-white, .bg-gradient-to-br, .bg-gradient-to-r').forEach((el, index) => {
        el.classList.add('fade-in');
        el.style.animationDelay = `${index * 0.1}s`;
    });
    
    function updateProgressBar() {
        let progress = 0;
        const incrementTime = 50;
        const incrementAmount = (100 / slideDuration) * incrementTime;
        
        progressInterval = setInterval(() => {
            progress += incrementAmount;
            if (progressBar) {
                progressBar.style.width = progress + '%';
            }
            
            if (progress >= 100) {
                clearInterval(progressInterval);
                progress = 0;
            }
        }, incrementTime);
    }
    
    function showSlide(index, direction = 'next') {
        clearInterval(progressInterval);
        
        slides.forEach((slide, i) => {
            slide.classList.remove('active', 'prev');
            if (direction === 'next') {
                if (i < index) slide.classList.add('prev');
            } else {
                if (i > index) slide.classList.add('prev');
            }
        });
        
        slides[index].classList.add('active');
        
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        
        currentSlide = index;
        
        if (progressBar) {
            progressBar.style.width = '0%';
        }
        setTimeout(updateProgressBar, 100);
    }
    
    function nextSlide() {
        const next = (currentSlide + 1) % totalSlides;
        showSlide(next, 'next');
    }
    
    function prevSlide() {
        const prev = (currentSlide - 1 + totalSlides) % totalSlides;
        showSlide(prev, 'prev');
    }
    
    function startAutoSlide() {
        autoSlideInterval = setInterval(nextSlide, slideDuration);
        updateProgressBar();
    }
    
    function stopAutoSlide() {
        clearInterval(autoSlideInterval);
        clearInterval(progressInterval);
    }
    
    function restartAutoSlide() {
        stopAutoSlide();
        startAutoSlide();
    }
    
    // Event listeners
    nextBtn?.addEventListener('click', () => {
        nextSlide();
        restartAutoSlide();
    });
    
    prevBtn?.addEventListener('click', () => {
        prevSlide();
        restartAutoSlide();
    });
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            restartAutoSlide();
        });
    });
    
    // Enhanced hover effects
    const carousel = document.getElementById('heroCarousel');
    carousel?.addEventListener('mouseenter', stopAutoSlide);
    carousel?.addEventListener('mouseleave', startAutoSlide);
    
    // Touch/Swipe support
    let touchStartX = 0;
    let touchEndX = 0;
    
    carousel?.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    carousel?.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                nextSlide();
            } else {
                prevSlide();
            }
            restartAutoSlide();
        }
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevSlide();
            restartAutoSlide();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            restartAutoSlide();
        }
    });
    
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all animated elements
    document.querySelectorAll('.fade-in').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    // Initialize carousel
    startAutoSlide();
    
    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href'))?.scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});
</script>
@endsection