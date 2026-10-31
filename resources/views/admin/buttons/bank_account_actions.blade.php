@if (backpack_user()->can($entry->business_id !== null ? 'can_update_business' : 'can_update_contact'))
    @if ($entry->archived_at === null)
        <form method="POST" action="{{ route('admin.bank-accounts.set-default', ['id' => $entry->getKey()]) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-link"><i class="la la-star"></i> {{ __('parties.bank_accounts.actions.make_default') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.bank-accounts.archive', ['id' => $entry->getKey()]) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-link"><i class="la la-archive"></i> {{ __('parties.actions.archive') }}</button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.bank-accounts.restore', ['id' => $entry->getKey()]) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-link"><i class="la la-undo"></i> {{ __('parties.actions.restore') }}</button>
        </form>
    @endif
@endif
