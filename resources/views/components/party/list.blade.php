@props([
    'parties',
    'showRoute',
    'routeKey',
    'search',
    'archived',
    'orderBy',
    'orderAsc',
    'defaultId' => null,
    'emptyTitle',
    'emptyMessage',
])

<div>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <div class="flex gap-2">
            <button type="button" wire:click="showArchived(false)"
                class="px-4 py-2 rounded-sm text-sm font-semibold {{ $archived ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' : 'bg-[#490BF4] text-white' }}">
                {{ __('parties.filters.active') }}
            </button>
            <button type="button" wire:click="showArchived(true)"
                class="px-4 py-2 rounded-sm text-sm font-semibold {{ $archived ? 'bg-[#490BF4] text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                {{ __('parties.filters.archived') }}
            </button>
        </div>
        <div class="flex gap-2">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('parties.placeholders.search') }}"
                aria-label="{{ __('parties.placeholders.search') }}"
                class="form-input w-full md:w-72 rounded-sm border-blue-100 bg-blue-50 px-4 py-2 text-base dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
            @if ($search !== '')
                <button type="button" wire:click="resetFilters" class="px-3 text-gray-500 hover:text-gray-900 dark:text-gray-300"
                    title="{{ __('parties.actions.reset_filters') }}" aria-label="{{ __('parties.actions.reset_filters') }}">
                    <i class="fas fa-times"></i>
                </button>
            @endif
        </div>
    </div>

    @if ($parties->total() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <x-sort-header key="name" :label="__('parties.fields.name')" :order-by="$orderBy" :order-asc="$orderAsc" />
                        <x-sort-header key="short_name" :label="__('parties.fields.short_name')" :order-by="$orderBy" :order-asc="$orderAsc" class="hidden lg:table-cell" />
                        <x-sort-header key="company_number" :label="__('parties.fields.company_number')" :order-by="$orderBy" :order-asc="$orderAsc" />
                        <x-sort-header key="email" :label="__('parties.fields.email')" :order-by="$orderBy" :order-asc="$orderAsc" class="hidden md:table-cell" />
                        <x-sort-header key="city" :label="__('parties.fields.city')" :order-by="$orderBy" :order-asc="$orderAsc" class="hidden md:table-cell" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($parties as $party)
                        <tr wire:key="party-{{ $party->id->value }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <a href="{{ route($showRoute, ['locale' => app()->getLocale(), $routeKey => $party->id->value]) }}"
                                    class="hover:text-[#490BF4]">{{ $party->profile->name }}</a>
                                @if ($defaultId !== null && $defaultId->equals($party->id))
                                    <span class="ml-2 rounded-sm bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">{{ __('businesses.fields.default') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ $party->profile->shortName ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $party->profile->registrationNumber?->number ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden md:table-cell">{{ $party->profile->email ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden md:table-cell">{{ $party->profile->address?->city ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $parties->links() }}
    @else
        <div class="text-center py-10">
            <div class="text-gray-400 mb-3"><i class="fas fa-address-book fa-3x"></i></div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ $emptyTitle }}</h3>
            <p class="text-gray-500 dark:text-gray-400">{{ $emptyMessage }}</p>
        </div>
    @endif
</div>
