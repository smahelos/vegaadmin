@extends('layouts.frontend')

@section('title', $plan->name . ' - ' . __('subscription.subscription_plans'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <div class="flex items-center space-x-2 text-sm text-gray-500">
                <a href="{{ route('subscriptions.index', ['locale' => app()->getLocale()]) }}" class="hover:text-blue-600">
                    {{ __('subscription.subscription_plans') }}
                </a>
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-gray-900 dark:text-white">{{ $plan->name }}</span>
            </div>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2">
            <!-- Plan Details -->
            <div class="relative z-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg shadow-gray-400 dark:shadow-gray-900 p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $plan->name }}</h1>
                        <div class="mb-4">
                            <span class="text-5xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($plan->price, 0) }}</span>
                            <span class="text-gray-600 dark:text-gray-400 ml-2">{{ $plan->currency }}</span>
                            <div class="text-lg text-gray-500 dark:text-gray-300">
                                {{ __('subscription.billing_' . $plan->billing_period) }}
                            </div>
                        </div>

                        @if($plan->hasTrialPeriod())
                        <div
                            class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800 mb-4">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ __('subscription.trial_days', ['days' => $plan->trial_days]) }}
                        </div>
                        @endif
                    </div>

                    <div class="mb-8">
                        <p class="text-gray-700 dark:text-gray-300 text-lg leading-relaxed">{{ $plan->description }}</p>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('subscription.features') }}</h3>
                        <ul class="space-y-3">
                            @foreach($plan->features as $feature)
                            <li class="flex items-start">
                                <svg class="h-6 w-6 text-green-500 dark:text-green-300 mt-0.5 mr-3 flex-shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">{{ $feature->name }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Payment Options -->
            <div class="relative z-10 -left-6 -top-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg shadow-gray-400 dark:shadow-gray-900 p-8 overflow-visible">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('subscription.complete_subscription') }}</h2>

                    @if($errors->any())
                    <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400 dark:text-red-300 mt-0.5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-300">{{ __('subscription.please_fix_the_following_errors') }}</h3>
                                <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('subscriptions.subscribe', ['locale' => app()->getLocale(), 'plan' => $plan]) }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Payment Gateway Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('subscription.choose_payment_method') }}
                            </label>
                            <div class="space-y-3">
                                @foreach($availableGateways as $gatewayKey => $gateway)
                                <label
                                    class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-300 dark:hover:border-blue-500 dark:text-white cursor-pointer">
                                    <input type="radio" name="gateway" value="{{ $gatewayKey }}"
                                        class="text-blue-600 focus:ring-blue-500 border-gray-300" {{ $loop->first ?
                                    'checked' : '' }}>
                                    <div class="ml-3 flex items-center">
                                        @if($gatewayKey === 'gopay')
                                        <img src="https://www.gopay.com/images/gopay-logo.svg" alt="GoPay"
                                            class="h-6 mr-3">
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $gateway['name'] }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('subscription.supports') }}: {{ implode(', ', $gateway['supported_currencies']) }}
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ __('subscription.order_summary') }}</h4>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">{{ $plan->name }} ({{ __('subscription.billing_' .
                                    $plan->billing_period) }})</span>
                                <span class="font-semibold dark:text-white">{{ $plan->formatted_price }}</span>
                            </div>
                            @if($plan->hasTrialPeriod())
                            <div class="text-sm text-green-600 dark:text-green-300 mt-1">
                                {{ __('subscription.trial_period') }}: {{ __('subscription.trial_days', ['days' => $plan->trial_days]) }}
                            </div>
                            @endif
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="flex items-start">
                            <input type="checkbox" id="terms" required
                                class="mt-1 text-blue-600 dark:text-blue-500 focus:ring-blue-500 border-gray-300 dark:border-gray-700 rounded">
                            <label for="terms" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('subscription.i_agree_to_the') }}
                                <a href="#" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 underline">{{ __('subscription.terms_service') }}</a>
                                {{ __('subscription.and') }}
                                <a href="#" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 underline">{{ __('subscription.privacy_policy') }}</a>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-lg transition-colors flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            {{ __('subscription.subscribe') }}
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('subscription.secure_payment_processed') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
