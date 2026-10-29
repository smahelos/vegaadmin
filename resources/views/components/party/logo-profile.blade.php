@props(['logo'])

<x-detail.item class="md:col-span-2" accent="border-red-300" :label="__('parties.fields.logo')">
    @if ($logo)
        <img src="{{ asset('storage/' . $logo) }}" alt="{{ __('parties.fields.logo') }}" class="mt-1 max-h-37 object-contain">
    @else
        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ __('parties.messages.no_logo') }}</span>
    @endif
</x-detail.item>
