@extends('layouts.frontend')

@section('title', __('pages.homepage'))

@push('after_styles')
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<div class="hero-section text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                {{ __('pages.homepage_empty_title') }}
            </h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
                {{ __('pages.homepage_empty_message') }}
            </p>

            <!-- Call to Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mt-8">
                @auth
                <a href="{{ route('frontend.dashboard') }}"
                    class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-200">
                    {{ __('navigation.dashboard') }}
                </a>
                @else
                <a href="{{ route('frontend.invoice.create.guest') }}"
                    class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-200">
                    {{ __('pages.create_invoice_guest') }}
                </a>
                <a href="{{ route('frontend.login') }}"
                    class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition duration-200">
                    {{ __('auth.login') }}
                </a>
                @endauth
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
                {{ __('invoices.features_title') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div
                        class="bg-purple-100 dark:bg-purple-900 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-invoice text-purple-600 dark:text-purple-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('invoices.easy_invoicing') }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __('invoices.easy_invoicing_desc') }}
                    </p>
                </div>
                <div class="text-center">
                    <div
                        class="bg-purple-100 dark:bg-purple-900 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-download text-purple-600 dark:text-purple-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('invoices.instant_download') }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __('invoices.instant_download_desc') }}
                    </p>
                </div>
                <div class="text-center">
                    <div
                        class="bg-purple-100 dark:bg-purple-900 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-purple-600 dark:text-purple-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('invoices.secure') }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __('invoices.secure_desc') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
