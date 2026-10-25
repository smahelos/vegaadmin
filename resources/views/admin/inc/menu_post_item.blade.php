{{-- A dropdown menu item for an action that changes state: a POST form styled as menu-dropdown-item --}}
<form method="POST" action="{{ $action }}" class="m-0">
    @csrf
    <button type="submit" class="dropdown-item">
        <i class="nav-icon {{ $icon }} d-block d-lg-none d-xl-block"></i>
        <span>{{ $title }}</span>
    </button>
</form>
