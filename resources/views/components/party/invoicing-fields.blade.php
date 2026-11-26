@props([
    'languages',
])

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <x-form.checkbox model="invoicing.isVatPayer" :label="__('businesses.fields.is_vat_payer')"
        :hint="__('businesses.hints.is_vat_payer')" />
    <x-form.input model="invoicing.registerEntry" :label="__('businesses.fields.register_entry')"
        :hint="__('businesses.hints.register_entry')" />
    <x-form.select model="invoicing.documentLanguage" :label="__('businesses.fields.document_language')"
        :options="$languages" required />
</div>
