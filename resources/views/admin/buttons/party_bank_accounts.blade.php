@php
    $ownerKey = $crud->get('party.entity') === 'business' ? 'business_id' : 'contact_id';
    $user = backpack_user();
@endphp
@if ($user->can($ownerKey === 'business_id' ? 'can_view_business' : 'can_view_contact'))
    <a href="{{ backpack_url('bank-account') . '?' . http_build_query([$ownerKey => $entry->getKey()]) }}" class="btn btn-sm btn-link">
        <i class="la la-university"></i> {{ __('parties.bank_accounts.entity.plural') }}
    </a>
@endif
