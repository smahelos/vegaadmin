{{-- Select2 field (Billex implementation): the free `select` field (options from a model) with select2 on top. --}}
@php
    if (!isset($field['allows_null'])) {
        $relationModel = $crud->getRelationModel($field['entity'], -1);
        $field['allows_null'] = $relationModel::isColumnNullable($field['name']);
    }
    $field['attributes'] = array_merge($field['attributes'] ?? [], [
        'data-init-function' => 'bpFieldInitBillexSelect2',
        'data-placeholder' => $field['placeholder'] ?? '',
        'data-allows-null' => $field['allows_null'] ? 'true' : 'false',
    ]);
@endphp

@include('crud::fields.select')
@include('admin.inc.select2_assets')
