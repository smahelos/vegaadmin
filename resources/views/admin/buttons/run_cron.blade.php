@if ($crud->hasAccess('update'))
    <a href="{{ url($crud->route.'/'.$entry->getKey().'/run') }}" 
       class="btn btn-sm btn-outline-primary" 
       data-toggle="tooltip" 
       title="{{ __('cron_tasks.actions.run_now') }}">
        <i class="la la-play"></i>
    </a>
@endif
