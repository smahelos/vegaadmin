<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->

    <!-- Styles -->
    @vite(['resources/css/app.css'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800;900&display=swap">

    @stack('before_styles')
    @stack('after_styles')

    <script>
        // On page load or when changing themes, best to add inline in `head` to avoid FOUC
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>

</head>

<body class="font-sans antialiased bg-[#F5F6FF] dark:bg-gray-900">
    <div class="min-h-screen">
        @include('layouts.navigation')

        <!-- Page Content -->
        <main class="pt-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if(session('success'))
                <div class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800"
                    role="alert">
                    <svg class="shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                    </svg>
                    <span class="sr-only">Info</span>
                    <div>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800"
                    role="alert">
                    <svg class="shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                    </svg>
                    <span class="sr-only">Info</span>
                    <div>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                </div>
                @endif

                @if(Session::has('last_guest_invoice_token') && Session::has('last_guest_invoice_number'))
                @php
                $now = time();
                $expiresAt = Session::get('last_guest_invoice_expires');
                $remainingTime = $expiresAt - $now;
                @endphp

                @if($remainingTime > 0)
                <div class="bg-blue-50 dark:bg-gray-900 border-l-4 border-blue-500 p-4 pr-0 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-blue-700 dark:text-blue-400">
                                {{ __('invoices.messages.last_invoice_available', ['number' =>
                                Session::get('last_guest_invoice_number')]) }}
                                <span class="font-medium">
                                    {{ __('invoices.messages.expires_in', ['minutes' => ceil($remainingTime / 60)]) }}
                                </span>
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('frontend.invoice.download.token', ['token' => Session::get('last_guest_invoice_token'), 'locale' => app()->getLocale()]) }}"
                                class="inline-flex items-right px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white hover:text-white bg-[#490BF4] hover:bg-indigo-500 transition-colors duration-200">
                                <i class="fas fa-download mr-2 !leading-[1.2]"></i> {{ __('invoices.actions.download')
                                }}
                            </a>
                            <a href="{{ route('frontend.invoice.delete.token', ['token' => Session::get('last_guest_invoice_token'), 'locale' => app()->getLocale()]) }}"
                                class="inline-flex items-right px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 hover:text-white bg-red-200 hover:bg-red-400 transition-colors duration-200"
                                onclick="return confirm('{{ __('invoices.messages.confirm_delete') }}');">
                                <i class="fas fa-trash-alt mr-2 !leading-[1.2]"></i> {{ __('invoices.actions.delete') }}
                            </a>
                        </div>
                    </div>
                </div>
                @else
                @php
                // Automatically delete the session data if expired
                Session::forget('last_guest_invoice_token');
                Session::forget('last_guest_invoice_number');
                Session::forget('last_guest_invoice_expires');
                @endphp
                @endif
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    {{-- Footer --}}
    @include('layouts.footer')

    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    @stack('before_scripts')
    @stack('scripts')
    @stack('after_scripts')
    @livewireScripts

    <!-- Flowbite JavaScript -->
    <script>
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // Change the icons inside the button based on previous settings
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');

        themeToggleBtn.addEventListener('click', function() {

            // toggle icons inside button
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            // if set via local storage previously
            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }

            // if NOT set via local storage previously
            } else {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            }

        });
    </script>
</body>

</html>
