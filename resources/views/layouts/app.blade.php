<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Add user meta information in head -->
    @auth
        @if (auth('owner')->check())
            <meta name="current-user-id" content="{{ auth('owner')->user()->id }}">
            <meta name="current-user-type" content="{{ get_class(auth('owner')->user()) }}">
        @elseif(auth('staff')->check())
            <meta name="current-user-id" content="{{ auth('staff')->user()->id }}">
            <meta name="current-user-type" content="{{ get_class(auth('staff')->user()) }}">
        @elseif(auth('customer')->check())
            <meta name="current-user-id" content="{{ auth('customer')->user()->id }}">
            <meta name="current-user-type" content="{{ get_class(auth('customer')->user()) }}">
        @endif
        
        <!-- ANTI-BACK-BUTTON META TAGS (For ALL authenticated users) -->
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, max-age=0, private">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
    @endauth
    
    <title>Linkud Hub - @yield('title')</title>
    
    <link rel="icon" href="{{ asset('storage/logo.png') }}" type="image/png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes pulse-smooth {
            0% {
                transform: scale(1);
                opacity: 0;
            }
            20% {
                opacity: 0.4;
            }
            70% {
                transform: scale(1.2);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 0;
            }
        }

        .animate-pulse-slow {
            animation: pulse-smooth 2s ease-out infinite;
        }
        
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>

    <!-- Global Toast Trigger Function -->
    <script>
        function showAppToast(type, message, duration = 5000) {
            if (!message) {
                console.warn('showAppToast called with no message.');
                return;
            }
            const event = new CustomEvent('show-toast', {
                detail: {
                    type: type,
                    message: String(message),
                    duration: duration
                }
            });
            window.dispatchEvent(event);
        }
        
        function isAuthenticated() {
            return document.querySelector('meta[name="current-user-id"]') !== null;
        }
        
        function getUserType() {
            const meta = document.querySelector('meta[name="current-user-type"]');
            return meta ? meta.getAttribute('content') : null;
        }
    </script>
</head>

<body>
    {{-- Don't display navbar on certain pages --}}
    @if (
        !request()->routeIs([
            'showCustomerRegistration',
            'showLoginForm',
            'password.request',
            'password.reset',
            'login.2fa.form'
        ]))
        @include('partials.navbar')
    @endif

    {{-- Main Content --}}
    <main>
        @yield('content')
    </main>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-20 right-6 z-[9999] w-80 max-w-xs flex flex-col gap-3"></div>

    <!-- ============================================================ -->
    <!-- ALPINE.JS - LOAD ONCE AND ONLY ONCE                          -->
    <!-- ============================================================ -->
    <!-- Option A: Using Vite (uncomment if using Vite) -->
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    
    <!-- Option B: Using CDN (recommended for simplicity) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    @stack('scripts')

    <!-- ANTI-BACK-BUTTON SCRIPT FOR ALL AUTHENTICATED USERS -->
    @auth
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userType = getUserType();
            console.log('Back button protection active for:', userType);
            
            if (window.history && window.history.pushState) {
                history.pushState(null, null, location.href);
                
                window.onpopstate = function(event) {
                    showAppToast('warning', 'Back navigation is disabled. Redirecting to current page...', 3000);
                    history.pushState(null, null, location.href);
                    setTimeout(() => {
                        window.location.reload();
                    }, 100);
                };
                
                window.addEventListener('popstate', function(e) {
                    showAppToast('info', 'Please use the application navigation instead of browser buttons.', 2000);
                    history.go(1);
                });
            }
            
            window.onpageshow = function(event) {
                if (event.persisted) {
                    console.log('Page was restored from cache, reloading...');
                    window.location.reload();
                }
            };
            
            if (window.performance && window.performance.getEntriesByType) {
                const entries = window.performance.getEntriesByType('navigation');
                if (entries.length > 0) {
                    const navEntry = entries[0];
                    if (navEntry.type === 'back_forward') {
                        console.log('Back/forward navigation detected, reloading...');
                        window.location.reload();
                    }
                }
            }
            
            window.addEventListener('load', function() {
                if (window.sessionStorage) {
                    Object.keys(sessionStorage).forEach(key => {
                        if (key.includes('form') || key.includes('cache')) {
                            sessionStorage.removeItem(key);
                        }
                    });
                }
            });
            
            document.onkeydown = function(e) {
                if (e.key === 'Backspace' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    e.preventDefault();
                    showAppToast('info', 'Backspace navigation is disabled', 2000);
                }
                if (e.altKey && e.key === 'ArrowLeft') {
                    e.preventDefault();
                    showAppToast('info', 'Alt+Left arrow navigation is disabled', 2000);
                }
            };
            
            let lastActivity = Date.now();
            document.addEventListener('click', () => lastActivity = Date.now());
            document.addEventListener('keypress', () => lastActivity = Date.now());
            
            setInterval(() => {
                const inactiveTime = Date.now() - lastActivity;
                if (inactiveTime > 5 * 60 * 1000) {
                    showAppToast('warning', 'Session will expire due to inactivity', 3000);
                }
            }, 30000);
            
            if (window.sessionStorage) {
                sessionStorage.setItem('lastAuthenticatedPage', window.location.href);
                if (document.referrer.includes('logout') || 
                    document.referrer.includes('login') ||
                    window.location.href.includes('login')) {
                    if (window.performance && window.performance.clearResourceTimings) {
                        performance.clearResourceTimings();
                    }
                }
            }
            
            const initialUrl = window.location.href;
            let lastUrl = initialUrl;
            setInterval(() => {
                if (window.location.href !== lastUrl && 
                    !window.location.href.includes('logout') &&
                    window.location.href !== initialUrl) {
                    console.log('URL changed unexpectedly, might be back button navigation');
                    showAppToast('warning', 'Redirecting to current session...', 2000);
                    window.history.replaceState(null, null, lastUrl);
                }
                lastUrl = window.location.href;
            }, 100);
            
            if (window !== window.top) {
                window.top.location = window.location;
            }
            
            window.onunload = function() {
                if (window.localStorage) {
                    localStorage.setItem('lastUnloadTime', Date.now());
                }
            };
            
            const redirectUrls = {
                'App\\Models\\Customer': "{{ route('sub_three.home.showHome', [], false) ?? '/' }}",
                'App\\Models\\Staff': "/staff/dashboard",
                'App\\Models\\Owner': "/owner/dashboard"
            };
            
            if (redirectUrls[userType]) {
                window.fallbackRedirect = redirectUrls[userType];
            }
        });
        
        function clearAllCaches() {
            if (window.localStorage) localStorage.clear();
            if (window.sessionStorage) sessionStorage.clear();
            if (window.caches) {
                caches.keys().then(function(names) {
                    for (let name of names) {
                        caches.delete(name);
                    }
                });
            }
        }
        
        document.addEventListener('logout', function() {
            clearAllCaches();
        });
        
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                console.log('Page hidden, clearing temporary caches...');
            }
        });
    </script>
    @endauth

    <!-- Session Flash Messages -->
    <script>
        (function() {
            const containerId = 'toast-container';

            function createToast(config) {
                const container = document.getElementById(containerId);
                if (!container) {
                    console.warn('Toast container not found');
                    return;
                }

                const toast = document.createElement('div');
                toast.className = `
                    w-full bg-white border border-gray-200 rounded-lg shadow-lg 
                    hover:bg-gray-50 transition-all ease-in-out opacity-0 translate-x-6 
                    duration-500 overflow-hidden cursor-pointer
                `;

                const { type, message, duration = 5000 } = config;

                let iconSvg = '';
                let iconColor = 'text-gray-500';
                let progressColor = 'bg-[#7F5539]';

                switch (type) {
                    case 'success':
                        iconSvg = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />';
                        iconColor = 'text-green-500';
                        progressColor = 'bg-green-500';
                        break;
                    case 'error':
                        iconSvg = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />';
                        iconColor = 'text-red-500';
                        progressColor = 'bg-red-500';
                        break;
                    case 'warning':
                        iconSvg = '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />';
                        iconColor = 'text-yellow-500';
                        progressColor = 'bg-yellow-500';
                        break;
                    case 'info':
                    default:
                        iconSvg = '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />';
                        iconColor = 'text-blue-500';
                        progressColor = 'bg-blue-500';
                        break;
                }

                toast.innerHTML = `
                    <div class="flex justify-between items-start p-3 rounded-lg hover:bg-gray-100">
                        <div class="flex items-start space-x-3 flex-1">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5 ${iconColor}" fill="currentColor" viewBox="0 0 20 20">
                                    ${iconSvg}
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800 mb-1">${message}</p>
                                <p class="text-xs text-gray-500">Just now</p>
                            </div>
                        </div>
                        <button class="close-toast-btn ml-2 p-1 rounded-full hover:bg-gray-200 focus:outline-none flex-shrink-0 transition-colors">
                            <svg class="w-4 h-4 text-gray-500 hover:text-gray-700" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <div class="progress-bar h-0.5 bg-gray-200 w-full">
                        <div class="h-full ${progressColor} transition-all duration-100 linear" style="width: 100%"></div>
                    </div>
                `;

                container.appendChild(toast);

                const progressBarInner = toast.querySelector('.progress-bar > div');
                const closeButton = toast.querySelector('.close-toast-btn');
                const fadeDuration = 500;

                let timer;
                let progressInterval;
                let remaining = duration;
                let startTime = Date.now();

                const pauseTimer = () => {
                    clearTimeout(timer);
                    clearInterval(progressInterval);
                    remaining -= (Date.now() - startTime);
                };

                const resumeTimer = () => {
                    startTime = Date.now();
                    clearTimeout(timer);
                    clearInterval(progressInterval);

                    if (remaining <= 0) {
                        closeToast();
                        return;
                    }

                    timer = setTimeout(closeToast, remaining);

                    progressInterval = setInterval(() => {
                        const timePassed = Date.now() - startTime;
                        const progress = ((remaining - timePassed) / duration) * 100;
                        if (progressBarInner) {
                            progressBarInner.style.width = `${Math.max(0, progress)}%`;
                        }
                    }, 50);
                };

                const closeToast = () => {
                    clearTimeout(timer);
                    clearInterval(progressInterval);
                    toast.classList.add('opacity-0', 'translate-x-6');

                    setTimeout(() => {
                        if (toast) toast.remove();
                    }, fadeDuration);
                };

                requestAnimationFrame(() => {
                    toast.classList.remove('opacity-0', 'translate-x-6');
                });

                toast.addEventListener('mouseenter', pauseTimer);
                toast.addEventListener('mouseleave', resumeTimer);
                closeButton.addEventListener('click', closeToast);

                resumeTimer();
            }

            document.addEventListener('DOMContentLoaded', function() {
                @if (session()->has('booking_success'))
                    createToast({ type: 'success', message: '{{ session('booking_success') }}', duration: 5000 });
                @endif

                @if (session()->has('checkin_success'))
                    createToast({ type: 'success', message: '{{ session('checkin_success') }}', duration: 5000 });
                @endif

                @if (session()->has('success'))
                    createToast({ type: 'success', message: '{{ session('success') }}', duration: 5000 });
                @endif

                @if (session()->has('error'))
                    createToast({ type: 'error', message: '{{ session('error') }}', duration: 7000 });
                @endif

                @if (session()->has('warning'))
                    createToast({ type: 'warning', message: '{{ session('warning') }}', duration: 6000 });
                @endif

                @if (session()->has('info'))
                    createToast({ type: 'info', message: '{{ session('info') }}', duration: 5000 });
                @endif

                @if (session()->has('toast'))
                    createToast({
                        type: '{{ session('toast.type') }}',
                        message: '{{ session('toast.message') }}',
                        duration: 5000
                    });
                @endif
            });

            // Listen for custom toast events
            window.addEventListener('show-toast', function(e) {
                const { type, message, duration } = e.detail;
                createToast({ type, message, duration });
            });
        })();
    </script>
</body>

</html>