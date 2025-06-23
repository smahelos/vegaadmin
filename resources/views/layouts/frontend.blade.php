<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Styles -->
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @stack('before_styles')
    @stack('after_styles')
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        @include('layouts.navigation')

        <!-- Page Content -->
        <main>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    @if(session('success'))
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-red-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                                </div>
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
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 pr-0 mb-6">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm text-blue-700">
                                            {{ __('invoices.messages.last_invoice_available', ['number' => Session::get('last_guest_invoice_number')]) }}
                                            <span class="font-medium">
                                                {{ __('invoices.messages.expires_in', ['minutes' => ceil($remainingTime / 60)]) }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="flex space-x-3">
                                        <a href="{{ route('frontend.invoice.download.token', ['token' => Session::get('last_guest_invoice_token'), 'locale' => app()->getLocale()]) }}" 
                                            class="inline-flex items-right px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 hover:text-white bg-blue-300 hover:bg-cyan-600">
                                            <i class="fas fa-download mr-2 !leading-[1.2]"></i> {{ __('invoices.actions.download') }}
                                        </a>
                                        <a href="{{ route('frontend.invoice.delete.token', ['token' => Session::get('last_guest_invoice_token'), 'locale' => app()->getLocale()]) }}" 
                                            class="inline-flex items-right px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 hover:text-white bg-red-200 hover:bg-red-400"
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
            </div>
        </main>
    </div>

    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    @stack('before_scripts')
    @stack('scripts')
    @stack('after_scripts')
</body>
</html>
