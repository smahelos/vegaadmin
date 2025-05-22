@include('crud::fields.inc.wrapper_start')
<label>{!! $field['label'] !!}</label>
@include('crud::fields.inc.translatable_icon')

<div class="mb-3">
    {{-- Image upload input --}}
    <div class="backstrap-file mt-2">
        <input type="file" name="{{ $field['name'] }}" id="{{ $field['name'] }}_input"
            @include('crud::fields.inc.attributes') accept="{{ $field['accept'] ?? 'image/*' }}">

        @if(!empty($field['hint']))
        <small class="form-text text-muted">{!! $field['hint'] !!}</small>
        @endif

        @if(!empty($entry) && !empty($entry->{$field['name']}))
        <div class="form-check mt-2">
            <input type="checkbox" class="form-check-input" name="{{ $field['name'] }}_remove"
                id="{{ $field['name'] }}_remove">
            <label class="form-check-label" for="{{ $field['name'] }}_remove">
                {{ trans('backpack::crud.clear') }} {{ $field['label'] }}
            </label>
        </div>
        @endif
    </div>

    {{-- Show existing image preview if available --}}
    <div class="existing-file mb-2" id="{{ $field['name'] }}_preview_wrapper"
        style="{{ (!empty($entry) && !empty($entry->{$field['name']})) ? '' : 'display: none;' }}">
        @if(!empty($entry) && !empty($entry->{$field['name']}))
        @php
        $filePath = $entry->{$field['name']};
        $fileUrl = method_exists($entry, 'getFileUrl') ? $entry->getFileUrl($field['name']) : asset('storage/' .
        $filePath);
        $thumbnailUrl = method_exists($entry, 'getImageThumbUrl') ? $entry->getImageThumbUrl($field['name']) : $fileUrl;
        @endphp

        <img id="{{ $field['name'] }}_preview" src="{{ $thumbnailUrl }}" alt="{{ $field['label'] }}"
            style="max-height: 200px; max-width: 100%;">
        @endif
    </div>
</div>

{{-- JAVASCRIPT FOR LIVE PREVIEW --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputField = document.getElementById('{{ $field['name'] }}_input');
        const previewWrapper = document.getElementById('{{ $field['name'] }}_preview_wrapper');
        const removeCheckbox = document.getElementById('{{ $field['name'] }}_remove');
        
        if (inputField) {
            inputField.addEventListener('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const file = event.target.files[0];
                    const fileType = file.type;
                    
                    // Only for images
                    if (fileType.startsWith('image/')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            previewWrapper.innerHTML = `
                                <img 
                                    id="{{ $field['name'] }}_preview" 
                                    src="${e.target.result}" 
                                    alt="Preview" 
                                    style="max-height: 200px; max-width: 100%;"
                                >`;
                            previewWrapper.style.display = 'block';
                        };
                        
                        reader.readAsDataURL(file);
                    }
                    
                    // Uncheck remove checkbox if present
                    if (removeCheckbox) {
                        removeCheckbox.checked = false;
                    }
                }
            });
        }
        
        // Handle remove checkbox to hide preview
        if (removeCheckbox) {
            removeCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    previewWrapper.style.display = 'none';
                } else {
                    previewWrapper.style.display = 'block';
                }
            });
        }
    });
</script>
@include('crud::fields.inc.wrapper_end')
