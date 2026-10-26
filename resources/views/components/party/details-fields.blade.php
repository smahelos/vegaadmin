@props([
    'countries',
    'registerMessage' => null,
])

<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <x-form.input class="md:col-span-4" model="party.name" :label="__('parties.fields.name')" required />
        <x-form.input class="md:col-span-2" model="party.shortName" :label="__('parties.fields.short_name')"
            :hint="__('parties.hints.short_name')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <x-form.select class="md:col-span-2" model="party.registrationCountry" :label="__('parties.fields.registration_country')"
            :options="$countries" />
        <div class="md:col-span-4">
            <x-form.input model="party.companyNumber" :label="__('parties.fields.company_number')"
                :hint="__('parties.hints.company_number')" />
            <button type="button" wire:click="lookUpCompany" wire:loading.attr="disabled" wire:target="lookUpCompany"
                class="mt-2 px-3 py-1 bg-indigo-50 text-[#490BF4] hover:bg-[#490BF4] hover:text-white dark:bg-indigo-500 dark:text-white rounded-sm text-sm font-medium transition-colors">
                <i class="fas fa-search mr-1"></i> {{ __('parties.actions.look_up_company') }}
            </button>
            @if ($registerMessage)
                <p class="mt-2 text-sm text-green-600">{{ $registerMessage }}</p>
            @endif
        </div>
    </div>

    <x-form.input model="party.vatNumber" :label="__('parties.fields.vat_number')" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-form.input type="email" model="party.email" :label="__('parties.fields.email')" />
        <x-form.input model="party.phone" :label="__('parties.fields.phone')" />
    </div>

    <h3 class="text-lg font-semibold text-gray-900 dark:text-white pt-2">{{ __('parties.sections.address') }}</h3>
    <x-form.input model="party.street" :label="__('parties.fields.street')" />
    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <x-form.input class="md:col-span-4" model="party.city" :label="__('parties.fields.city')" />
        <x-form.input class="md:col-span-2" model="party.postalCode" :label="__('parties.fields.postal_code')" />
    </div>
    <x-form.select model="party.country" :label="__('parties.fields.country')" :options="$countries" />

    <x-form.textarea model="party.note" :label="__('parties.fields.note')" :hint="__('parties.hints.note')" />
</div>
