@php
    $entity = $crud->get('party.entity');
    if ($entity === 'business') {
        $routes = ['archive' => 'admin.businesses.archive', 'restore' => 'admin.businesses.restore'];
        $permissions = ['archive' => 'can_archive_business', 'restore' => 'can_restore_business'];
    } else {
        $routes = ['archive' => 'admin.contacts.archive', 'restore' => 'admin.contacts.restore'];
        $permissions = ['archive' => 'can_archive_contact', 'restore' => 'can_restore_contact'];
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
