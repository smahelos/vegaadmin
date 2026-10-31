@props([
    'party',
    'countries',
])

<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-detail.item class="md:col-span-2" :label="__('parties.fields.name')">{{ $party->name }}</x-detail.item>
        <x-detail.item accent="border-blue-500" :label="__('parties.fields.email')">{{ $party->email }}</x-detail.item>
        <x-detail.item accent="border-blue-500" :label="__('parties.fields.phone')">{{ $party->phone }}</x-detail.item>
    </div>
    <x-party.billing-profile :profile="$party" :countries="$countries" />
</div>
