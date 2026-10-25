@if (backpack_user()->can('can_create_edit_cron_task'))
    <form method="POST" action="{{ route('admin.cron-tasks.run', ['id' => $entry->getKey()]) }}" class="d-inline">
        @csrf
        <button type="submit"
            class="btn btn-sm btn-outline-primary"
            data-toggle="tooltip"
            title="{{ __('cron_tasks.actions.run_now') }}">
            <i class="la la-play"></i>
        </button>
    </form>
@endif
