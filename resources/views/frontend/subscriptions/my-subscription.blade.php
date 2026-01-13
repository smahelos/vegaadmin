@extends('layouts.frontend')

@section('title', __('subscription.my_subscription'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('subscription.my_subscription') }}</h1>

            @if($subscription->isActive())
            <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-700 text-green-800 dark:text-green-200">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                {{ __('subscription.status.active') }}
            </span>
            @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                {{ __('subscription.status.' . $subscription->status) }}
            </span>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Subscription Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Current Plan -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('subscription.current_plan') }}</h2>

                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-blue-600 mb-2">{{ $subscription->subscriptionPlan->name
                                }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $subscription->subscriptionPlan->description }}</p>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-300">{{ __('Price') }}:</span>
                                    <span class="font-semibold ml-1 dark:text-white">{{ $subscription->subscriptionPlan->formatted_price
                                        }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-300">{{ __('Billing') }}:</span>
                                    <span class="font-semibold ml-1 dark:text-white">{{ __('subscription.billing_' .
                                        $subscription->subscriptionPlan->billing_period) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($subscription->amount, 0) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-300">{{ $subscription->currency }}</div>
                        </div>
                    </div>
                </div>

                <!-- Subscription Timeline -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('Subscription Details') }}</h2>

                    <div class="space-y-4">
                        @if($subscription->starts_at)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Started') }}:</span>
                            <span class="font-medium dark:text-white">{{ $subscription->starts_at->format('d.m.Y H:i') }}</span>
                        </div>
                        @endif

                        @if($subscription->trial_ends_at)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Trial ends') }}:</span>
                            <span
                                class="font-medium {{ $subscription->isInTrial() ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $subscription->trial_ends_at->format('d.m.Y H:i') }}
                                @if($subscription->isInTrial())
                                <span class="text-xs text-green-600 dark:text-green-400 ml-1">({{ __('Active') }})</span>
                                @endif
                            </span>
                        </div>
                        @endif

                        @if($subscription->next_billing_at)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Next billing') }}:</span>
                            <span class="font-medium dark:text-white">{{ $subscription->next_billing_at->format('d.m.Y') }}</span>
                        </div>
                        @endif

                        @if($subscription->ends_at)
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Expires') }}:</span>
                            <span
                                class="font-medium {{ $subscription->daysUntilExpiration() < 7 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $subscription->ends_at->format('d.m.Y') }}
                                @if($subscription->daysUntilExpiration() !== null)
                                <span class="text-xs ml-1">
                                    ({{ __('subscription.days_until_expiration', ['days' =>
                                    $subscription->daysUntilExpiration()]) }})
                                </span>
                                @endif
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('subscription.features') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($subscription->subscriptionPlan->features as $feature)
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-400">{{ $feature->name }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Actions') }}</h3>

                    <div class="space-y-3">
                        <a href="{{ route('subscriptions.index', ['locale' => app()->getLocale()]) }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2 border border-blue-300 dark:border-blue-700 rounded-sm shadow-sm text-sm font-bold text-blue-700 dark:text-blue-200 bg-blue-50 dark:bg-blue-600 hover:bg-blue-100 dark:hover:bg-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                            </svg>
                            {{ __('subscription.upgrade_plan') }}
                        </a>

                        @if($subscription->isActive())
                        <form action="{{ route('subscriptions.cancel', ['locale' => app()->getLocale(), 'subscription' => $subscription]) }}" method="POST"
                            onsubmit="return confirm('{{ __('Are you sure you want to cancel your subscription?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-red-300 dark:border-red-700 rounded-sm shadow-sm text-sm font-bold text-red-700 dark:text-red-200 bg-red-50 dark:bg-red-600 hover:bg-red-100 dark:hover:bg-red-500 transition-colors cursor-pointer">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                {{ __('subscription.cancel_subscription') }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Support -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Need Help?') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('Contact our support team for any questions about your subscription.') }}
                    </p>
                    <a href="mailto:support@example.com" class="text-sm text-blue-600 dark:text-blue-300 hover:text-blue-700 dark:hover:text-blue-400 font-medium">
                        {{ __('Contact Support') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        @if($subscription->payments->count() > 0)
        <div class="mt-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('Payment History') }}</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Date') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Amount') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    {{ __('Gateway') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($subscription->payments->sortByDesc('created_at') as $payment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $payment->created_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $payment->status === 'completed' ? 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200' : 
                                                   ($payment->status === 'failed' ? 'bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200' : 'bg-yellow-100 dark:bg-yellow-600 text-yellow-800 dark:text-yellow-200') }}">
                                        {{ __('payment.status.' . $payment->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white capitalize">
                                    {{ $payment->gateway }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
