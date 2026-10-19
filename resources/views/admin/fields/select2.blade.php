{{-- Select2 field (Billex implementation): the free `select` field (options from a model) with select2 on top. --}}
@php
    $field['allows_null'] = $field['allows_null'] ?? $crud->getRelationModel($field['entity'], -1)::isColumnNullable($field['name']);
    $field['attributes'] = array_merge($field['attributes'] ?? [], [
        'data-init-function' => 'bpFieldInitBillexSelect2',
        'data-placeholder' => $field['placeholder'] ?? '',
        'data-allows-null' => $field['allows_null'] ? 'true' : 'false',
    ]);
@endphp

@include('crud::fields.select')
@include('admin.inc.select2_assets')
