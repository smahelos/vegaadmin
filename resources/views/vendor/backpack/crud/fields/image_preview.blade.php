@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <div class="mb-3">
        {{-- Image upload input --}}
        <div class="backstrap-file mt-2">
            <input type="file"
                   name="{{ $field['name'] }}"
                   id="{{ $field['name'] }}_input"
                   @include('crud::fields.inc.attributes')
                   accept="image/*">
            
            @if(!empty($field['hint']))
                <small class="form-text text-muted">{!! $field['hint'] !!}</small>
            @endif

            @if(!empty($entry) && !empty($entry->{$field['name']}))
                <div class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" name="{{ $field['name'] }}_remove" 
                           id="{{ $field['name'] }}_remove">
                    <label class="form-check-label" for="{{ $field['name'] }}_remove">
                        {{ trans('admin.products.remove_image') }}
                    </label>
                </div>
            @endif
        </div>
        {{-- Show existing image if available --}}
        <div class="existing-image mb-2" id="{{ $field['name'] }}_preview_wrapper" 
             style="{{ (!empty($entry) && !empty($entry->{$field['name']})) ? '' : 'display: none;' }}">
            <img 
                id="{{ $field['name'] }}_preview" 
                src="{{ (!empty($entry) && !empty($entry->{$field['name']})) ? asset('storage/' . $entry->{$field['name']}) : '' }}" 
                alt="{{ $field['label'] }}" 
                style="max-height: 200px; max-width: 100%;"
            >
        </div>
    </div>

    {{-- JAVASCRIPT FOR LIVE PREVIEW --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputField = document.getElementById('{{ $field['name'] }}_input');
            const previewImage = document.getElementById('{{ $field['name'] }}_preview');
            const previewWrapper = document.getElementById('{{ $field['name'] }}_preview_wrapper');
            const removeCheckbox = document.getElementById('{{ $field['name'] }}_remove');
            
            if (inputField) {
                inputField.addEventListener('change', function(event) {
                    if (event.target.files && event.target.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            // Update preview image source
                            previewImage.src = e.target.result;
                            // Make sure preview is visible
                            previewWrapper.style.display = 'block';
                            
                            // If remove checkbox exists, uncheck it when a new image is selected
                            if (removeCheckbox) {
                                removeCheckbox.checked = false;
                            }
                        };
                        
                        reader.readAsDataURL(event.target.files[0]);
                    }
                });
            }
            
            // Handle remove checkbox to hide preview when checked
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
