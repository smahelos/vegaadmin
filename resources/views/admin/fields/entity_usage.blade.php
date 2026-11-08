{{-- The owner's usage of the plan limit of this record (D-047), read-only; reloaded when the selected owner changes. --}}
@include('crud::fields.inc.wrapper_start')
    <label>{{ $field['label'] }}</label>
    <div data-entity-usage data-url="{{ route('admin.entity-usage.show', ['entity' => $field['limited_entity']]) }}" data-owner="{{ $entry->user_id ?? '' }}"></div>
@include('crud::fields.inc.wrapper_end')

@include('admin.inc.entity_usage_styles')

@push('crud_fields_scripts')
    @once
        @vite('resources/js/admin/entity-usage-field.js')
    @endonce
@endpush
