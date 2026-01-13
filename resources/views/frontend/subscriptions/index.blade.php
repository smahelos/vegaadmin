@extends('layouts.frontend')

@section('title', __('subscription.subscription_plans'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            {{ __('subscription.subscription_plans') }}
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
            {{ __('Choose the perfect plan for your business needs') }}
        </p>
    </div>

    @if($userSubscription)
    <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-8">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                    {{ __('subscription.subscription_active') }}
                </h3>
                <div class="mt-2 text-sm text-green-600 dark:text-green-300">
                    <p>{{ __('You have an active subscription:') }} <strong>{{ $userSubscription->subscriptionPlan->name
                            }}</strong></p>
                    <a href="{{ route('subscriptions.my-subscription', ['locale' => app()->getLocale()]) }}"
                        class="font-medium underline dark:text-green-300 hover:text-green-600 dark:hover:text-green-200">
                        {{ __('subscription.my_subscription') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($plans as $plan)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-sm overflow-hidden transform hover:scale-105 transition-transform duration-200 
                {{ $plan->billing_period === 'yearly' ? 'ring-2 ring-blue-500' : '' }}">

            @if($plan->billing_period === 'yearly')
            <div class="bg-blue-500 dark:bg-blue-800 text-white text-center py-2 text-sm font-medium">
                {{ __('Best Value - 2 months free!') }}
            </div>
            @endif

            <div class="p-6">
                <div class="text-center">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $plan->name }}
                    </h3>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($plan->price, 0) }}</span>
                        <span class="text-gray-600 dark:text-gray-400 ml-1">{{ $plan->currency }}</span>
                        <div class="text-sm text-gray-500 dark:text-gray-300">
                            {{ __('subscription.billing_' . $plan->billing_period) }}
                        </div>
                    </div>

                    @if($plan->hasTrialPeriod())
                    <div
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 mb-4">
                        {{ __('subscription.trial_days', ['days' => $plan->trial_days]) }}
                    </div>
                    @endif
                </div>

                <div class="mb-6">
                    <p class="text-gray-600 dark:text-gray-400 text-center mb-4">{{ $plan->description }}</p>

                    <ul class="space-y-2">
                        @foreach($plan->features as $feature)
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 dark:text-green-300 mt-0.5 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-400">{{ $feature->name }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="text-center">
                    @if($userSubscription && $userSubscription->subscriptionPlan->id === $plan->id)
                    <button disabled
                        class="w-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-bold py-3 px-4 rounded-lg cursor-not-allowed">
                        {{ __('subscription.current_plan') }}
                    </button>
                    @elseif($userSubscription)
                    <a href="{{ route('subscriptions.show', ['plan' => $plan, 'locale' => app()->getLocale()]) }}"
                        class="w-full inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                        {{ __('subscription.upgrade_plan') }}
                    </a>
                    @else
                    <a href="{{ route('subscriptions.show', ['plan' => $plan, 'locale' => app()->getLocale()]) }}"
                        class="w-full inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                        {{ __('subscription.choose_plan') }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="text-center mt-12">
        <p class="text-gray-600">
            {{ __('Need help choosing a plan?') }}
            <a href="mailto:support@example.com" class="text-blue-600 hover:text-blue-700 font-medium">
                {{ __('Contact our support team') }}
            </a>
        </p>
    </div>
</div>
@endsection
