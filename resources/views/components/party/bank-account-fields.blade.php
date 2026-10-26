@props([
    'bankOptions',
    'countries',
])

<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-form.input model="account.label" :label="__('parties.bank_accounts.fields.label')"
            :hint="__('parties.bank_accounts.hints.label')" />
        <x-form.select model="account.country" :label="__('parties.bank_accounts.fields.country')" :options="$countries" live />
    </div>
    <x-form.input model="account.iban" :label="__('parties.bank_accounts.fields.iban')"
        :hint="__('parties.bank_accounts.hints.iban')" />
    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <x-form.input class="md:col-span-3" model="account.number" :label="__('parties.bank_accounts.fields.number')"
            :hint="__('parties.bank_accounts.hints.number')" />
        @if ($bankOptions !== [])
            <x-form.select class="md:col-span-3" model="account.bankCode" :label="__('parties.bank_accounts.fields.bank_code')"
                :options="$bankOptions" />
        @else
            <x-form.input class="md:col-span-3" model="account.bankCode" :label="__('parties.bank_accounts.fields.bank_code')" />
        @endif
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-form.input model="account.swift" :label="__('parties.bank_accounts.fields.swift')" />
        <x-form.input model="account.bankName" :label="__('parties.bank_accounts.fields.bank_name')" />
    </div>
</div>
