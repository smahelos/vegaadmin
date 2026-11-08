{{-- A role limit (D-047): mode select plus a number used only for the limited mode. --}}
@include('crud::fields.inc.wrapper_start')
    <x-admin.role-limit :field="$field" :editing="isset($entry)" />
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    @once
        @vite('resources/js/admin/role-limit-field.js')
    @endonce
@endpush
