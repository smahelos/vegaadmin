@php
    $entity = $crud->get('party.entity');
    $routes = $entity === 'business'
        ? ['archive' => 'admin.businesses.archive', 'restore' => 'admin.businesses.restore']
        : ['archive' => 'admin.contacts.archive', 'restore' => 'admin.contacts.restore'];
    $permissions = $entity === 'business'
        ? ['archive' => 'can_archive_business', 'restore' => 'can_restore_business']
        : ['archive' => 'can_archive_contact', 'restore' => 'can_restore_contact'];
    $action = $entry->archived_at === null ? 'archive' : 'restore';
@endphp
@if (backpack_user()->can($permissions[$action]))
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
