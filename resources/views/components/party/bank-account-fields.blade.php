@props([
    'bankOptions',
    'countries',
    'names' => null,
    'labelled' => true,
])

<div class="space-y-5">
    @if ($labelled)
        <x-form.input class="md:w-1/2" model="account.label" :label="__('parties.bank_accounts.fields.label')"
            :hint="__('parties.bank_accounts.hints.label')" />
    @endif
    <div class="flex flex-col md:flex-row gap-6">
        <x-form.select class="md:w-20 md:shrink-0" model="account.country" :name="$names ? $names . '_country' : null" :label="__('parties.bank_accounts.fields.country')"
            :options="$countries" live short />
        <x-form.input class="md:flex-1" model="account.number" :name="$names ? $names . '_number' : null" :label="__('parties.bank_accounts.fields.number')"
            :hint="__('parties.bank_accounts.hints.number')" />
        @if ($bankOptions !== [])
            <x-form.select class="md:w-28 md:shrink-0" model="account.bankCode" :name="$names ? $names . '_bank_code' : null" :label="__('parties.bank_accounts.fields.bank_code')"
                :options="$bankOptions" short />
        @else
            <x-form.input class="md:w-28 md:shrink-0" model="account.bankCode" :name="$names ? $names . '_bank_code' : null" :label="__('parties.bank_accounts.fields.bank_code')" />
        @endif
        <x-form.input class="md:flex-1" model="account.bankName" :name="$names ? $names . '_bank_name' : null" :label="__('parties.bank_accounts.fields.bank_name')" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
        <x-form.input class="md:col-span-5" model="account.iban" :name="$names ? $names . '_iban' : null" :label="__('parties.bank_accounts.fields.iban')"
            :hint="__('parties.bank_accounts.hints.iban')" />
        <x-form.input class="md:col-span-5" model="account.swift" :name="$names ? $names . '_swift' : null" :label="__('parties.bank_accounts.fields.swift')" />
    </div>
</div>
