@props(['account'])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-detail.item accent="border-orange-300" :label="__('parties.bank_accounts.fields.number')">
        @if ($account->account->number)
            {{ $account->account->number }}/{{ $account->account->bankCode }}
        @endif
    </x-detail.item>
    <x-detail.item accent="border-purple-300" :label="__('parties.bank_accounts.fields.bank_name')">{{ $account->bankName }}</x-detail.item>
    <x-detail.item accent="border-blue-300" :label="__('parties.bank_accounts.fields.iban')">{{ $account->account->formattedIban() }}</x-detail.item>
    <x-detail.item accent="border-red-300" :label="__('parties.bank_accounts.fields.swift')">{{ $account->account->swift }}</x-detail.item>
</div>
