@props(['profile'])

<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-detail.item :label="__('parties.fields.name')">{{ $profile->name }}</x-detail.item>
        <x-detail.item :label="__('parties.fields.short_name')">{{ $profile->shortName }}</x-detail.item>
        <x-detail.item accent="border-orange-500" :label="__('parties.fields.email')">{{ $profile->email }}</x-detail.item>
        <x-detail.item accent="border-orange-500" :label="__('parties.fields.phone')">{{ $profile->phone }}</x-detail.item>
        {{ $slot }}
    </div>

    @if ($profile->note)
        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('parties.fields.note') }}</div>
            <div class="text-sm whitespace-pre-line text-gray-900 dark:text-white">{{ $profile->note }}</div>
        </div>
    @endif
</div>
