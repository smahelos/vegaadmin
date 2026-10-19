{{-- Multilingual Textarea Field --}}
@include('crud::fields.inc.wrapper_start')

<div class="multilingual-field">
    <!-- Language Tabs -->
    <ul class="nav nav-tabs" role="tablist">
        @foreach(config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $index => $locale)
        <li class="nav-item" role="presentation">
            <button class="nav-link @if($index === 0) active @endif" id="{{ $field['name'] }}-{{ $locale }}-tab"
                data-bs-toggle="tab" data-bs-target="#{{ $field['name'] }}-{{ $locale }}" type="button" role="tab">
                {{ strtoupper($locale) }}
                @if($index === 0 && ($field['name'] === 'name' || $field['name'] === 'slug'))
                <span class="text-danger">*</span>
                <small class="text-muted">(alespoň jeden jazyk)</small>
                @endif
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
                rows="{{ $field['rows'] ?? 4 }}" @if(isset($field['readonly']) && $field['readonly']) readonly @endif
                @if(isset($field['disabled']) && $field['disabled']) disabled @endif
                placeholder="{{ $field['label'] ?? '' }} ({{ strtoupper($locale) }})">{{ $fieldValue }}</textarea>

            @error($fieldName)
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @endforeach
    </div>
</div>

@include('crud::fields.inc.wrapper_end')
