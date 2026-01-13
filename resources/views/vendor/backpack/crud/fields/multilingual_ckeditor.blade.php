{{-- Multilingual CKEditor Field --}}
@php
$field['extra_plugins'] = isset($field['extra_plugins']) ? implode(',', $field['extra_plugins']) : "embed,widget";

$defaultOptions = [
"filebrowserBrowseUrl" => backpack_url('elfinder/ckeditor'),
"extraPlugins" => $field['extra_plugins'],
"embed_provider" => "//ckeditor.iframe.ly/api/oembed?url={url}&callback={callback}",
"height" => $field['height'] ?? 300,
];

$field['options'] = array_merge($defaultOptions, $field['options'] ?? []);
@endphp

@include('crud::fields.inc.wrapper_start')

<div class="multilingual-field">
    <!-- Language Tabs -->
    <ul class="nav nav-tabs" role="tablist">
        @foreach(config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $index => $locale)
        <li class="nav-item" role="presentation">
            <button class="nav-link @if($index === 0) active @endif" id="{{ $field['name'] }}-{{ $locale }}-tab"
                data-bs-toggle="tab" data-bs-target="#{{ $field['name'] }}-{{ $locale }}" type="button" role="tab">
                {{ strtoupper($locale) }}
            </button>
        </li>
        @endforeach
    </ul>

    <!-- Tab Content -->
    <div class="tab-content border border-top-0 p-3">
        @foreach(config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $index => $locale)
        <div class="tab-pane fade @if($index === 0) show active @endif" id="{{ $field['name'] }}-{{ $locale }}"
            role="tabpanel">

            @php
            $fieldName = $field['name'] . '_' . $locale;
            $fieldValue = old($fieldName) ?? (isset($entry) ? data_get($entry, $field['name'] . '.' . $locale) : '');
            @endphp

            <textarea name="{{ $fieldName }}" class="form-control @error($fieldName) is-invalid @enderror"
                id="{{ $fieldName }}_ckeditor" data-init-function="bpFieldInitCKEditorElement"
                data-options="{{ trim(json_encode($field['options'])) }}" @if(isset($field['readonly']) &&
                $field['readonly']) readonly @endif @if(isset($field['disabled']) && $field['disabled']) disabled @endif
                placeholder="{{ $field['label'] ?? '' }} ({{ strtoupper($locale) }})">{{ $fieldValue }}</textarea>

            @error($fieldName)
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @endforeach
    </div>
</div>

@include('crud::fields.inc.wrapper_end')

{{-- Load CKEditor scripts only once --}}
@if ($crud->fieldTypeNotLoaded($field))
@php
$crud->markFieldTypeAsLoaded($field);
@endphp

@push('crud_fields_scripts')
<script src="{{ asset('packages/ckeditor/ckeditor.js') }}"></script>
<script src="{{ asset('packages/ckeditor/adapters/jquery.js') }}"></script>
<script>
    function bpFieldInitCKEditorElement(element) {
                // trigger a new CKEditor
                element.ckeditor(element.data('options'));
            }

            // Initialize CKEditor for all multilingual textareas when tabs are shown
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize visible CKEditor (first tab)
                @foreach(config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $index => $locale)
                @if($index === 0)
                var element{{ $locale }} = $('#{{ $field['name'] }}_{{ $locale }}_ckeditor');
                if (element{{ $locale }}.length) {
                    bpFieldInitCKEditorElement(element{{ $locale }});
                }
                @endif
                @endforeach

                // Initialize CKEditor when tab is clicked
                $('[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                    var target = $(e.target).attr('data-bs-target');
                    var textarea = $(target).find('textarea[data-init-function="bpFieldInitCKEditorElement"]');
                    if (textarea.length && !textarea.hasClass('ckeditor-initialized')) {
                        textarea.addClass('ckeditor-initialized');
                        bpFieldInitCKEditorElement(textarea);
                    }
                });
            });
</script>
@endpush

@push('crud_fields_styles')
<style>
    .multilingual-field .tab-content {
        min-height: 350px;
    }
</style>
@endpush
@endif
