{{--
    Relationship field (Billex implementation): select2 on top of the free `select` field for
    single relations (BelongsTo, HasOne) or `select_multiple` for many relations. Options as for the
    free fields: entity, model, attribute, options (query closure), allows_null.
--}}
@php
    $multiple = $crud->guessIfFieldHasMultipleFromRelationType($field['relation_type'] ?? 'BelongsTo');

    if (! isset($field['allows_null'])) {
        if (empty($field['entity'])) {
            // no relation method found: the field name is the foreign key column itself
            $field['allows_null'] = $crud->model::isColumnNullable($field['name']);
        } else {
            $relation = (new ($crud->getRelationModel($field['entity'], -1)))->{\Illuminate\Support\Str::afterLast($field['entity'], '.')}();
            $field['allows_null'] = $multiple
                || ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo
                    && $relation->getParent()::isColumnNullable($relation->getForeignKeyName()));
        }
    }

    $field['attributes'] = array_merge($field['attributes'] ?? [], [
        'data-init-function' => 'bpFieldInitBillexSelect2',
        'data-placeholder' => $field['placeholder'] ?? '',
        'data-allows-null' => $field['allows_null'] ? 'true' : 'false',
    ]);
@endphp

@include($multiple ? 'crud::fields.select_multiple' : 'crud::fields.select')
@include('admin.fields.select2_assets')
