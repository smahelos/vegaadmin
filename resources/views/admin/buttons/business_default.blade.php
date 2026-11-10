@use('App\Domain\User\Enums\Permission')
@php($user = backpack_user())
@if ($entry->archived_at === null && $entry->user?->default_business_id !== $entry->getKey() && $user->can(Permission::BUSINESS_SET_DEFAULT))
    <form method="POST" action="{{ route('admin.businesses.set-default', ['id' => $entry->getKey()]) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-link" title="{{ __('businesses.actions.make_default') }}">
            <i class="la la-star"></i> {{ __('businesses.actions.make_default') }}
        </button>
    </form>
@endif
