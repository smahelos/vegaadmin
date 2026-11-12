@use('App\Application\Party\Queries\ListBankAccounts')
@php
    $ownerKey = $crud->get('party.entity') === 'business' ? 'business_id' : 'contact_id';
@endphp
@allowed(ListBankAccounts::class)
    <a href="{{ backpack_url('bank-account') . '?' . http_build_query([$ownerKey => $entry->getKey()]) }}" class="btn btn-sm btn-link">
        <i class="la la-university"></i> {{ __('parties.bank_accounts.entity.plural') }}
    </a>
@endallowed
