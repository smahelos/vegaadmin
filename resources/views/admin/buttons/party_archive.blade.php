@use('App\Domain\User\Enums\Permission')
@php
    $entity = $crud->get('party.entity');
    if ($entity === 'business') {
        $routes = ['archive' => 'admin.businesses.archive', 'restore' => 'admin.businesses.restore'];
        $permissions = ['archive' => Permission::BUSINESS_ARCHIVE, 'restore' => Permission::BUSINESS_RESTORE];
    } else {
        $routes = ['archive' => 'admin.contacts.archive', 'restore' => 'admin.contacts.restore'];
        $permissions = ['archive' => Permission::CONTACT_ARCHIVE, 'restore' => Permission::CONTACT_RESTORE];
    }
    $action = $entry->archived_at === null ? 'archive' : 'restore';
    $user = backpack_user();
@endphp
@if ($user->can($permissions[$action]))
    @if ($entity === 'business' && $action === 'archive')
        <x-admin.business-archive :business-id="$entry->getKey()" :user-id="$entry->user_id" :is-default="$entry->user?->default_business_id === $entry->getKey()" />
    @else
        <form method="POST" action="{{ route($routes[$action], ['id' => $entry->getKey()]) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-link" title="{{ __('parties.actions.' . $action) }}">
                <i class="la {{ $action === 'archive' ? 'la-archive' : 'la-undo' }}"></i> {{ __('parties.actions.' . $action) }}
            </button>
        </form>
    @endif
@endif
