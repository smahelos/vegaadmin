@props([
    'profile',
    'countries',
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-detail.item accent="border-purple-500" :label="__('parties.fields.company_number')">
        @if ($profile->registrationNumber)
            {{ $profile->registrationNumber->number }}
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $countries[$profile->registrationNumber->country->value] ?? $profile->registrationNumber->country->value }})</span>
        @endif
    </x-detail.item>
    <x-detail.item accent="border-purple-500" :label="__('parties.fields.vat_number')">{{ $profile->vatNumber?->value }}</x-detail.item>
    <x-detail.item class="md:col-span-2" accent="border-orange-500" :label="__('parties.fields.street')">{{ $profile->address?->street }}</x-detail.item>
    <x-detail.item accent="border-orange-500" :label="__('parties.fields.city')">{{ $profile->address?->city }}</x-detail.item>
    <x-detail.item accent="border-red-500" :label="__('parties.fields.postal_code')">{{ $profile->address?->postalCode }}</x-detail.item>
    <x-detail.item accent="border-red-500" :label="__('parties.fields.country')">
        @if ($profile->address)
            {{ $countries[$profile->address->country->value] ?? $profile->address->country->value }}
        @endif
    </x-detail.item>
</div>
