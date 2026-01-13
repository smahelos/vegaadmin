{{--
UELS Limit Widget Component
Displays current usage vs limit for an entity type
This component supports both server-side rendering with initial data and JavaScript updates with live data
--}}
@props([
'entityType',
'currentUsage' => 0,
'limit' => 0,
'entityName' => '',
'showModal' => true
])

@php
$percentage = $limit > 0 ? min(100, ($currentUsage / $limit) * 100) : 0;
$isNearLimit = $percentage >= 80;
$isOverLimit = $currentUsage >= $limit;

// Color classes based on usage
$progressClass = match(true) {
$isOverLimit => 'bg-red-500',
$isNearLimit => 'bg-yellow-500',
default => 'bg-green-500'
};

$textClass = match(true) {
$isOverLimit => 'text-red-600 dark:text-red-400',
$isNearLimit => 'text-yellow-600 dark:text-yellow-400',
default => 'text-green-600 dark:text-green-400'
};

$iconClass = match(true) {
$isOverLimit => 'fa-exclamation-triangle text-red-500',
$isNearLimit => 'fa-exclamation-circle text-yellow-500',
default => 'fa-check-circle text-green-500'
};
@endphp

<div class="bg-white joke-{{ $limit }} dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4"
    data-uels-widget="{{ $entityType }}" data-entity-type="{{ $entityType }}">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-2">
            <i class="fas {{ $iconClass }}" data-uels-icon></i>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white" data-uels-title>
                {{ __('uels.widget.title') }} - {{ $entityName }}
            </h3>
        </div>

        @if($showModal)
        <button type="button" onclick="openUelsModal('{{ $entityType }}')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors cursor-pointer"
            title="{{ __('uels.widget.show_details') }}">
            <i class="fas fa-info-circle"></i>
        </button>
        @endif
    </div>

    <div class="space-y-2">
        <!-- Usage Statistics -->
        <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-300">{{ __('uels.widget.usage') }}</span>
            <span class="{{ $textClass }} font-medium" data-uels-usage data-uels-status>
                @if($currentUsage === 0 && $limit === 0)
                <i class="fas fa-spinner fa-spin text-gray-400"></i> {{ __('uels.widget.loading') }}
                @else
                {{ $currentUsage }} / {{ $limit === -1 ? __('uels.widget.unlimited') : $limit }}
                @endif
            </span>
        </div>

        @if($limit > 0)
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2" data-uels-progress-container>
            <div class="{{ $progressClass }} h-2 rounded-full transition-all duration-300" data-uels-progress
                style="width: {{ min(100, $percentage) }}%"></div>
        </div>

        <!-- Percentage -->
        <div class="text-right">
            <span class="text-xs {{ $textClass }}" data-uels-percentage>{{ number_format($percentage, 1) }}%</span>
        </div>
        @endif

        <!-- Alert Messages -->
        <div data-uels-alerts>
            @if($isOverLimit && $limit > 0)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-2 mt-2">
                <p class="text-xs text-red-700 dark:text-red-300">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ __('uels.widget.limit_exceeded') }}
                </p>
            </div>
            @elseif($isNearLimit && $limit > 0)
            <div
                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-2 mt-2">
                <p class="text-xs text-yellow-700 dark:text-yellow-300">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    {{ __('uels.widget.approaching_limit') }}
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
