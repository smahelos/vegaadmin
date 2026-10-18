{{-- Select2 from array field (Billex implementation): the free `select_from_array` field with select2 on top. --}}
@php
    $field['allows_null'] = $field['allows_null'] ?? $crud->model::isColumnNullable($field['name']);
    $field['attributes'] = array_merge($field['attributes'] ?? [], [
        'data-init-function' => 'bpFieldInitBillexSelect2',
        'data-placeholder' => $field['placeholder'] ?? '',
        'data-allows-null' => $field['allows_null'] ? 'true' : 'false',
    ]);
@endphp

@include('crud::fields.select_from_array')
@include('admin.fields.select2_assets')
