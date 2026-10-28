@props([
    'countries',
    'registerMessage' => null,
    'form' => 'party',
    'lookUp' => 'lookUpCompany',
    'names' => null,
    'required' => false,
])

<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
        <x-form.select class="md:col-span-3" model="{{ $form }}.registrationCountry" :name="$names ? $names . '_registration_country' : null" :label="__('parties.fields.registration_country')"
            :options="$countries" />
        <div class="md:col-span-4">
            <x-form.input model="{{ $form }}.companyNumber" :name="$names ? $names . '_company_number' : null" :label="__('parties.fields.company_number')"
                :hint="__('parties.hints.company_number')">
                <x-slot:append>
                    <button type="button" wire:click="{{ $lookUp }}" wire:loading.attr="disabled" wire:target="{{ $lookUp }}"
                        title="{{ __('parties.actions.look_up_company') }}" aria-label="{{ __('parties.actions.look_up_company') }}"
                        class="shrink-0 px-3 rounded-sm text-white bg-[#490BF4] hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer disabled:opacity-50 transition-colors duration-200">
                        <i class="fas fa-search" wire:loading.remove wire:target="{{ $lookUp }}"></i>
                        <i class="fas fa-spinner fa-spin" wire:loading wire:target="{{ $lookUp }}"></i>
                    </button>
                </x-slot:append>
            </x-form.input>
            @if ($registerMessage)
                <p class="mt-2 text-sm text-green-600">{{ $registerMessage }}</p>
            @endif
        </div>
        <x-form.input class="md:col-span-3" model="{{ $form }}.vatNumber" :name="$names ? $names . '_vat_number' : null" :label="__('parties.fields.vat_number')" />
    </div>

    <h3 class="text-lg font-semibold text-gray-900 dark:text-white pt-2">{{ __('parties.sections.address') }}</h3>
    <x-form.input model="{{ $form }}.street" :required="$required" :name="$names ? $names . '_street' : null" :label="__('parties.fields.street')" />
    <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
        <x-form.input class="md:col-span-4" model="{{ $form }}.city" :required="$required" :name="$names ? $names . '_city' : null" :label="__('parties.fields.city')" />
        <x-form.input class="md:col-span-3" model="{{ $form }}.postalCode" :required="$required" :name="$names ? $names . '_postal_code' : null" :label="__('parties.fields.postal_code')" />
        <x-form.select class="md:col-span-3" model="{{ $form }}.country" :required="$required" :name="$names ? $names . '_country' : null" :label="__('parties.fields.country')" :options="$countries" />
    </div>
</div>
