@props([
    'invoicing',
    'languages',
])

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-detail.item accent="border-indigo-500" :label="__('businesses.fields.is_vat_payer')">
        {{ $invoicing->vatPayer ? __('general.placeholders.yes') : __('general.placeholders.no') }}
    </x-detail.item>
    <x-detail.item accent="border-indigo-500" :label="__('businesses.fields.document_language')">
        {{ $languages[$invoicing->documentLanguage->value] }}
    </x-detail.item>
    <x-detail.item accent="border-indigo-500" :label="__('businesses.fields.register_entry')">{{ $invoicing->registerEntry }}</x-detail.item>
</div>
