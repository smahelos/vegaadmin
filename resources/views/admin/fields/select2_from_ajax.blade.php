{{--
    Select2 from ajax field (Billex implementation): one related record searched through `data_source`
    (see bpFieldInitBillexSelect2Ajax for the expected response). Options: model, attribute,
    data_source, placeholder, minimum_input_length, allows_null.
--}}
@php
    $relatedModel = new $field['model'];
    $currentValue = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? null;
    if ($currentValue instanceof \Illuminate\Database\Eloquent\Model) {
        $currentValue = $currentValue->getKey();
    }
    $currentEntry = filled($currentValue) ? $field['model']::find($currentValue) : null;
    $field['allows_null'] = $field['allows_null'] ?? $crud->model::isColumnNullable($field['name']);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <select name="{{ $field['name'] }}"
            data-init-function="bpFieldInitBillexSelect2Ajax"
            data-source="{{ $field['data_source'] }}"
            data-key="{{ $relatedModel->getKeyName() }}"
            data-attribute="{{ $field['attribute'] }}"
            data-minimum-input-length="{{ $field['minimum_input_length'] ?? 0 }}"
            data-placeholder="{{ $field['placeholder'] ?? '' }}"
            data-allows-null="{{ $field['allows_null'] ? 'true' : 'false' }}"
            data-dependencies='@json($field["dependencies"] ?? [])'
            @if (isset($field['counterparty_role']))
                data-counterparty-role="{{ $field['counterparty_role'] }}"
            @endif
            data-stored-reference-id="{{ $field['stored_reference_id'] ?? '' }}"
            data-document-business-role="{{ $field['document_business_role'] ?? '' }}"
            data-document-account="{{ !empty($field['document_account']) ? 'true' : 'false' }}"
            data-bank-transfer-method="{{ $field['bank_transfer_method_id'] ?? '' }}"
            data-select-default="{{ !empty($field['select_default']) ? 'true' : 'false' }}"
            @include('crud::fields.inc.attributes', ['default_class' => 'form-control form-select'])>
        @if ($field['allows_null'])
            <option value=""></option>
        @endif
        @if ($currentEntry)
            <option value="{{ $currentEntry->getKey() }}" selected>{{ isset($field['display_value']) ? $field['display_value']($currentEntry) : $currentEntry->{$field['attribute']} }}</option>
        @endif
    </select>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@include('admin.inc.select2_assets')
