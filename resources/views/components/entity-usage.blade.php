<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-2 flex justify-between gap-2 text-sm">
        <span id="{{ $meterId }}-label" class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
        <span class="font-medium text-gray-900 dark:text-white">
            @if ($usage->limit === null)
                {{ __('role_limits.usage.unlimited') }}
            @elseif ($usage->limit === 0)
                {{ __('role_limits.usage.not_allowed') }}
            @else
                {{ $usage->used }} / {{ $usage->limit }}
            @endif
        </span>
    </div>
    @if ($usage->limit > 0)
        <meter id="{{ $meterId }}" aria-labelledby="{{ $meterId }}-label" class="block h-2 w-full" min="0"
            max="{{ $usage->limit }}" low="{{ $low }}" high="{{ $high }}" optimum="0" value="{{ $usage->used }}"></meter>
    @endif
</div>
