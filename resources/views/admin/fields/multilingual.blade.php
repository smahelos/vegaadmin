{{--
    Multilingual field (Billex implementation): one tab per locale of app.available_locales, each holding a field
    of type 'field_type' (default text; e.g. textarea, summernote) named "{name}_{locale}" with the value of
    "{name}.{locale}" of the entry. The CRUD controller merges the "{name}_{locale}" inputs back into the
    multilingual attribute. 'attributes' and 'options' are passed to the field of every locale.
--}}
@php
    $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
    $fieldType = $field['field_type'] ?? 'text';
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>

    <ul class="nav nav-tabs" role="tablist">
        @foreach ($locales as $index => $locale)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $index === 0 ? 'active' : '' }}" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#{{ $field['name'] }}-{{ $locale }}">{{ strtoupper($locale) }}</button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content multilingual-tabs border border-top-0 p-3">
        @foreach ($locales as $index => $locale)
            @php
                $localeField = [
                    'name' => $field['name'] . '_' . $locale,
                    'type' => $fieldType,
                    'label' => '',
                    'value' => isset($entry) ? data_get($entry, $field['name'] . '.' . $locale) : null,
                    'attributes' => array_merge(['placeholder' => strip_tags($field['label']) . ' (' . strtoupper($locale) . ')'], $field['attributes'] ?? []),
                    'options' => $field['options'] ?? [],
                    'wrapper' => ['class' => 'mb-0'],
                ];
            @endphp
            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $field['name'] }}-{{ $locale }}" role="tabpanel">
                @includeFirst(\Backpack\CRUD\ViewNamespaces::getViewPathsFor('fields', $fieldType), ['field' => $localeField])
            </div>
        @endforeach
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@if ($fieldType === 'summernote')
    @include('admin.inc.summernote_theme')
@endif

@push('crud_fields_styles')
    @once
        <style>
            /* the field of each locale has no label, the tab names the locale */
            .multilingual-tabs [bp-field-wrapper] > label:empty { display: none; }
        </style>
    @endonce
@endpush
