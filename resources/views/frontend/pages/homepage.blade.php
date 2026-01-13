@extends('layouts.frontend')

@section('title', $page ? $page->name[$locale] : __('pages.homepage'))

@push('after_styles')
<style>
    .hero-section {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        text-align: left;
    }

    .invoice_bottom_grad {
        width: 100%;
        min-height: 150px;
        bottom: -70px;
        background: linear-gradient(to top, #F5F6FF 0 40%, transparent);
    }

    .dark .invoice_bottom_grad {
        background: linear-gradient(to top, var(--color-gray-900) 0 40%, transparent);
    }

    /* Animation for floating instructions */
    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-5px);
        }
    }

    .instruction-line {
        position: relative;
        display: inline-block;
        min-width: 350px;
    }

    .instruction-line::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        border-radius: 1px;
    }

    .instruction-dot {
        width: 8px;
        height: 8px;
        background: #6366f1;
        border-radius: 50%;
        position: absolute;
        bottom: -12px;
        right: -4px;
    }

    .dot-left {
        left: -4px !important;
    }

    .floating-animation {
        animation: float 4s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<div class="hero-section">
    <div class="place_hero_image_here">
        <!-- Hero Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 relative z-10">
            <div class="text-center">
                <!-- Main Heading -->
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">
                    Create beautiful invoices <span class="text-indigo-600">for free</span><br>
                    and get paid by your customers
                </h1>

                <!-- CTA Button -->
                <div class="mt-8 mb-4">
                    @auth
                    <a href="{{ route('frontend.dashboard', ['locale' => app()->getLocale()]) }}"
                        class="bg-[#490BF4] hover:bg-indigo-600 text-white font-semibold py-4 px-8 rounded-lg text-lg transition-colors duration-200 inline-block">
                        {{ __('general.navigation.dashboard') }}
                    </a>
                    @else
                    <a href="{{ route('frontend.invoice.create.guest', ['locale' => app()->getLocale()]) }}"
                        class="bg-[#490BF4] hover:bg-indigo-600 text-white font-semibold py-4 px-8 rounded-lg text-lg transition-colors duration-200 inline-block">
                        Create Invoice
                    </a>
                    @endauth
                </div>

                <!-- Sub text -->
                @guest
                <p class="text-gray-600 text-sm mb-16">
                    Or <a href="{{ route('frontend.register', ['locale' => app()->getLocale()]) }}"
                        class="text-[#490BF4] hover:text-indigo-600 underline">create account</a> and manage your
                    invoices for your business
                </p>
                @endguest
            </div>

            <!-- Hero Animation Container -->
            <div class="relative max-w-6xl mx-auto">
                <!-- Central Invoice Preview -->
                <div class="text-center mb-16">
                    <div id="invoice-preview"
                        class="inline-block bg-white rounded-2xl shadow-2xl p-10 transform translate-y-10 opacity-0 transition-all duration-1000 ease-out max-w-2xl w-full">
                        <!-- Invoice Header -->
                        <div class="flex justify-between items-start mb-8">
                            <div>
                                <div class="text-2xl font-bold text-gray-800 mb-2">Logo</div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-indigo-600 mb-2">Invoice <span
                                        class="text-black">#2025001</span></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-8 mb-8 text-left">
                            <div>
                                <div class="text-indigo-600 text-base font-medium mb-2">Supplier</div>
                                <div class="h-3 bg-gray-50 rounded w-3/4 mb-3"></div>
                                <div class="h-3 bg-gray-50 rounded mb-3"></div>
                                <div class="h-3 bg-gray-50 rounded mb-3"></div>
                            </div>
                            <div>
                                <div class="text-indigo-600 text-base font-medium mb-2">Customer</div>
                                <div class="h-3 bg-gray-50 rounded w-3/4 mb-3"></div>
                                <div class="h-3 bg-gray-50 rounded mb-3"></div>
                                <div class="h-3 bg-gray-50 rounded mb-3"></div>
                            </div>
                        </div>

                        <!-- Invoice Sections -->
                        <div class="grid grid-cols-2 gap-8 mb-8 text-left">
                            <div>
                                <div class="text-indigo-600 text-base font-medium mb-2">Instructions</div>
                                <div class="h-3 bg-gray-50 rounded w-3/4 mb-3"></div>
                                <div class="h-3 bg-gray-50 rounded mb-3"></div>
                                <div class="h-3 bg-gray-50 rounded mb-3"></div>
                            </div>
                            <div>
                                <div class="text-indigo-600 text-base font-medium mb-2">Dates</div>
                                <div class="h-3 bg-gray-50 rounded w-3/4 mb-3"></div>
                                <div class="h-3 bg-gray-50 rounded mb-3"></div>
                                <div class="h-3 bg-gray-50 rounded mb-3"></div>
                            </div>
                        </div>

                        <!-- Items Section -->
                        <div class="mb-8 text-left">
                            <div class="text-indigo-600 text-base font-medium mb-4">Items</div>
                            <div class="space-y-3">
                                <div class="h-5 bg-gray-100 rounded"></div>
                                <div class="h-5 bg-gray-100 rounded w-3/4"></div>
                            </div>
                            <button
                                class="mt-5 text-indigo-600 text-base border border-indigo-600 rounded-full px-5 py-2 hover:bg-indigo-50 transition-colors">
                                + add item
                            </button>
                        </div>

                        <!-- Download Button -->
                        <div class="text-right">
                            <button
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-4 px-8 rounded-lg text-base transition-colors duration-200">
                                Download PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Gradient overlay to mask bottom of invoice -->
                <div
                    class="invoice_bottom_grad absolute bottom-0 left-0 right-0 h-60 bg-gradient-to-t from-gray-0 via-gray-100/80 to-transparent pointer-events-none rounded-b-2xl">
                </div>

                <!-- Instructions Around Invoice -->
                <div class="absolute inset-0 pointer-events-none">
                    <!-- Step 1: Upload Logo (Left) -->
                    <div id="step-1"
                        class="absolute left-0 top-14 transform -translate-x-full opacity-0 transition-all duration-800 ease-out delay-500">
                        <div class="flex items-end space-x-0">
                            <div class="mr-3">
                                <div class="text-base font-medium text-gray-900 dark:text-white instruction-line pb-1">
                                    Upload Your Logo
                                    <div class="instruction-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Set Dates (Left) -->
                    <div id="step-2"
                        class="absolute left-0 top-48 transform -translate-x-full opacity-0 transition-all duration-800 ease-out delay-700">
                        <div class="flex items-end space-x-0">
                            <div class="mr-3">
                                <div class="text-base font-medium text-gray-900 dark:text-white instruction-line pb-1">
                                    Set dates and bank account
                                    <div class="instruction-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Add Items (Left) -->
                    <div id="step-3"
                        class="absolute left-0 top-82 transform -translate-x-full opacity-0 transition-all duration-800 ease-out delay-900">
                        <div class="flex items-end space-x-0">
                            <div class="mr-3">
                                <div class="text-base font-medium text-gray-900 dark:text-white instruction-line pb-1">
                                    Add items to invoice
                                    <div class="instruction-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Add Customer (Right) -->
                    <div id="step-4"
                        class="absolute right-0 top-22 transform translate-x-full opacity-0 transition-all duration-800 ease-out delay-1100">
                        <div class="flex items-end space-x-0">
                            <div class="ml-3">
                                <div
                                    class="text-base text-right font-medium text-gray-900 dark:text-white instruction-line pb-1">
                                    Add Customer credentials
                                    <div class="instruction-dot dot-left"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Set Due Date (Right) -->
                    <div id="step-5"
                        class="absolute right-0 top-58 transform translate-x-full opacity-0 transition-all duration-800 ease-out delay-1300">
                        <div class="flex items-end space-x-0">
                            <div class="ml-3">
                                <div
                                    class="text-base text-right font-medium text-gray-900 dark:text-white instruction-line pb-1">
                                    Set due date
                                    <div class="instruction-dot dot-left"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Send Invoice (Right) -->
                    <div id="step-6"
                        class="absolute right-0 bottom-20 transform translate-x-full opacity-0 transition-all duration-800 ease-out delay-1500">
                        <div class="flex items-end space-x-0">
                            <div class="ml-3">
                                <div
                                    class="text-base text-right font-medium text-gray-900 dark:text-white instruction-line pb-1">
                                    Send or download invoice
                                    <div class="instruction-dot dot-left"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feature  Pages -->
@if($featurePages && $featurePages->count() > 0)
<div class="bg-[#F5F6FF] dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
            <span class="border-b-3 border-[#490BF4]">{{ __('pages.titles.features') }}</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $featurePageNum = 1;
            @endphp
            @foreach($featurePages as $featurePage)
            <div class="bg-[#490BF4] dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-lg transition duration-200 
                @if(
                    $featurePageNum === 1 ||
                    $featurePageNum === 3 ||
                    $featurePageNum === 4 ||
                    $featurePageNum === 6) mb-8
                @elseif($featurePageNum === 2 ||
                    $featurePageNum === 5) mt-8 @endif">

                @php
                $featurePageNum++;
                @endphp

                @if($featurePage->image)
                <div class="aspect-w-16 aspect-h-9">
                    <img src="{{ $featurePage->image }}" alt="{{ $featurePage->name }}"
                        class="object-cover rounded-t-lg">
                </div>
                @endif
                <div class="p-8">
                    <h3 class="text-xl font-semibold text-white mb-2">
                        {{ $featurePage->name[$locale] }}
                    </h3>
                    @if($featurePage->description[$locale])
                    <p class="text-gray-400 mb-4">
                        {{ Str::limit($featurePage->description[$locale], 100) }}
                    </p>
                    @endif
                    <a href="{{ route('frontend.pages.show', ['slug' => $featurePage->slug[$locale], 'locale' => app()->getLocale()]) }}"
                        class="inline-flex items-center text-white hover:underline font-medium">
                        {{ __('pages.read_more') }}
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($pricePage)
<div class="">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="prose dark:prose-invert mx-auto">
            {!! $pricePage->getContent() !!}
        </div>
    </div>
</div>
@endif

@endsection

@push('after_scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate invoice preview
        const invoicePreview = document.getElementById('invoice-preview');
        
        // Start invoice animation
        setTimeout(() => {
            invoicePreview.classList.remove('translate-y-10', 'opacity-0');
            invoicePreview.classList.add('translate-y-0', 'opacity-100');
        }, 300);

        // Animate instruction steps from left
        const leftSteps = ['step-1', 'step-2', 'step-3'];
        leftSteps.forEach((stepId, index) => {
            const step = document.getElementById(stepId);
            setTimeout(() => {
                step.classList.remove('-translate-x-full', 'opacity-0');
                step.classList.add('translate-x-0', 'opacity-100');
            }, 800 + (index * 200));
        });

        // Animate instruction steps from right
        const rightSteps = ['step-4', 'step-5', 'step-6'];
        rightSteps.forEach((stepId, index) => {
            const step = document.getElementById(stepId);
            setTimeout(() => {
                step.classList.remove('translate-x-full', 'opacity-0');
                step.classList.add('translate-x-0', 'opacity-100');
            }, 1400 + (index * 200));
        });

        // Add continuous subtle floating animation to steps
        function addFloatingAnimation() {
            const allSteps = [...leftSteps, ...rightSteps];
            allSteps.forEach((stepId, index) => {
                const step = document.getElementById(stepId);
                if (step) {
                    setTimeout(() => {
                        step.classList.add('floating-animation');
                        step.style.animationDelay = `${index * 0.3}s`;
                    }, 2500);
                }
            });
        }

        // Start floating animation
        addFloatingAnimation();
    });
</script>
@endpush
