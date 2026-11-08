<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    @if ($usage === null)
        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</div>
        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ __('role_limits.usage.unavailable') }}</p>
    @else
        <div class="mb-3 flex items-center justify-between gap-2 text-sm">
            <span class="flex items-center gap-2">
                <i @class([
                    'fas',
                    'fa-exclamation-triangle text-red-500' => $full,
                    'fa-exclamation-circle text-yellow-500' => $near,
                    'fa-check-circle text-green-500' => !$full && !$near,
                ]) aria-hidden="true"></i>
                <span id="{{ $barId }}-label" class="font-medium text-gray-900 dark:text-white">{{ $label }}</span>
            </span>
            <span @class([
                'font-medium',
                'text-red-600 dark:text-red-400' => $full,
                'text-yellow-600 dark:text-yellow-400' => $near,
                'text-green-600 dark:text-green-400' => !$full && !$near,
            ])>
                @if ($usage->limit === null)
                    {{ __('role_limits.statuses.unlimited') }}
                @elseif ($usage->limit === 0)
                    {{ __('role_limits.statuses.not_allowed') }}
                @else
                    {{ $usage->used }} / {{ $usage->limit }}
                @endif
            </span>
        </div>
        @if ($usage->limit > 0)
            <progress id="{{ $barId }}" aria-labelledby="{{ $barId }}-label" max="{{ $usage->limit }}" value="{{ $usage->used }}"
                @class([
                    'block h-2 w-full appearance-none overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700',
                    '[&::-webkit-progress-bar]:bg-gray-200 dark:[&::-webkit-progress-bar]:bg-gray-700',
                    '[&::-webkit-progress-value]:rounded-full [&::-webkit-progress-value]:transition-all',
                    '[&::-moz-progress-bar]:rounded-full',
                    '[&::-webkit-progress-value]:bg-red-500 [&::-moz-progress-bar]:bg-red-500' => $full,
                    '[&::-webkit-progress-value]:bg-yellow-500 [&::-moz-progress-bar]:bg-yellow-500' => $near,
                    '[&::-webkit-progress-value]:bg-green-500 [&::-moz-progress-bar]:bg-green-500' => !$full && !$near,
                ])></progress>
            <div @class([
                'mt-1 text-right text-xs',
                'text-red-600 dark:text-red-400' => $full,
                'text-yellow-600 dark:text-yellow-400' => $near,
                'text-green-600 dark:text-green-400' => !$full && !$near,
            ])>{{ $percent }}</div>
            @if ($full || $near)
                <p @class([
                    'mt-2 rounded-md border p-2 text-xs',
                    'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300' => $full,
                    'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' => $near,
                ])>
                    @if ($full)
                        {{ __('role_limits.usage.reached') }}
                    @else
                        {{ __('role_limits.usage.approaching') }}
                    @endif
                    <a href="{{ $plansUrl }}" class="font-medium underline">{{ __('subscriptions.actions.view_plans') }}</a>
                </p>
            @endif
        @endif
    @endif
</div>
