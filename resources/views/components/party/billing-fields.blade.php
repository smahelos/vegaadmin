@props([
    'countries',
    'registerMessage' => null,
])

<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
        <x-form.select class="md:col-span-3" model="party.registrationCountry" :label="__('parties.fields.registration_country')"
            :options="$countries" />
        <div class="md:col-span-4">
            <x-form.input model="party.companyNumber" :label="__('parties.fields.company_number')"
                :hint="__('parties.hints.company_number')">
                <x-slot:append>
                    <button type="button" wire:click="lookUpCompany" wire:loading.attr="disabled" wire:target="lookUpCompany"
                        title="{{ __('parties.actions.look_up_company') }}" aria-label="{{ __('parties.actions.look_up_company') }}"
                        class="shrink-0 px-3 rounded-sm text-white bg-[#490BF4] hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer disabled:opacity-50 transition-colors duration-200">
                        <i class="fas fa-search" wire:loading.remove wire:target="lookUpCompany"></i>
                        <i class="fas fa-spinner fa-spin" wire:loading wire:target="lookUpCompany"></i>
                    </button>
                </x-slot:append>
            </x-form.input>
            @if ($registerMessage)
                <p class="mt-2 text-sm text-green-600">{{ $registerMessage }}</p>
            @endif
        </div>
        <x-form.input class="md:col-span-3" model="party.vatNumber" :label="__('parties.fields.vat_number')" />
    </div>

    <h3 class="text-lg font-semibold text-gray-900 dark:text-white pt-2">{{ __('parties.sections.address') }}</h3>
    <x-form.input model="party.street" :label="__('parties.fields.street')" />
    <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
        <x-form.input class="md:col-span-4" model="party.city" :label="__('parties.fields.city')" />
        <x-form.input class="md:col-span-3" model="party.postalCode" :label="__('parties.fields.postal_code')" />
        <x-form.select class="md:col-span-3" model="party.country" :label="__('parties.fields.country')" :options="$countries" />
    </div>
</div>
