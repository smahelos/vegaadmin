@props([
    'bankOptions',
    'countries',
])

<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
        <x-form.input class="md:col-span-5" model="account.label" :label="__('parties.bank_accounts.fields.label')"
            :hint="__('parties.bank_accounts.hints.label')" />
        <x-form.select class="md:col-span-5" model="account.country" :label="__('parties.bank_accounts.fields.country')" :options="$countries" live />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
        <x-form.input class="md:col-span-4" model="account.number" :label="__('parties.bank_accounts.fields.number')"
            :hint="__('parties.bank_accounts.hints.number')" />
        @if ($bankOptions !== [])
            <x-form.select class="md:col-span-3" model="account.bankCode" :label="__('parties.bank_accounts.fields.bank_code')"
                :options="$bankOptions" />
        @else
            <x-form.input class="md:col-span-3" model="account.bankCode" :label="__('parties.bank_accounts.fields.bank_code')" />
        @endif
        <x-form.input class="md:col-span-3" model="account.bankName" :label="__('parties.bank_accounts.fields.bank_name')" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
        <x-form.input class="md:col-span-5" model="account.iban" :label="__('parties.bank_accounts.fields.iban')"
            :hint="__('parties.bank_accounts.hints.iban')" />
        <x-form.input class="md:col-span-5" model="account.swift" :label="__('parties.bank_accounts.fields.swift')" />
    </div>
</div>
