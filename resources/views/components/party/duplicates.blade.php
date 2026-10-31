@props([
    'blocking',
    'warnings',
])

@if ($blocking)
    <div class="mb-6 rounded-sm border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200">
        <p>{{ $blocking['archived'] ? __('parties.messages.duplicate_blocking_archived') : __('parties.messages.duplicate_blocking') }}</p>
        <a href="{{ $blocking['url'] }}" class="mt-2 inline-block font-semibold underline">
            {{ $blocking['name'] !== '' ? $blocking['name'] : __('parties.actions.open_duplicate') }}
        </a>
    </div>
@endif

@if ($warnings !== [])
    <div class="mb-6 rounded-sm border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200">
        <p class="font-semibold">{{ __('parties.messages.duplicate_warning') }}</p>
        <ul class="mt-2 list-disc pl-5">
            @foreach ($warnings as $warning)
                <li>
                    <a href="{{ $warning['url'] }}" class="underline" target="_blank">{{ $warning['name'] }}</a>
                    — {{ __('parties.messages.duplicate_reasons.' . $warning['reason']) }}
                    @if ($warning['archived'])
                        ({{ __('parties.statuses.archived') }})
                    @endif
                </li>
            @endforeach
        </ul>
        <button type="button" wire:click="confirmDuplicates"
            class="mt-3 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-sm text-white text-sm font-semibold">
            {{ __('parties.actions.save_anyway') }}
        </button>
    </div>
@endif
