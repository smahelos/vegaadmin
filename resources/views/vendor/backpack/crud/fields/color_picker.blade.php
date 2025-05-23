<!-- configurable color picker -->
@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    <div class="input-group color-picker-component">
        <input
            type="text"
            name="{{ $field['name'] }}"
            value="{{ old(square_brackets_to_dots($field['name'])) ?? $field['value'] ?? $field['default'] ?? '' }}"
            data-init-function="bpFieldInitColorPickerElement"
            @include('crud::fields.inc.attributes')
            >
        <span class="input-group-text color-preview-{{ $field['name'] }}"><i></i></span>
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->fieldTypeNotLoaded($field))
    @php
        $crud->markFieldTypeAsLoaded($field);
    @endphp

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
        <link rel="stylesheet" href="{{ asset('packages/pickr/dist/themes/classic.min.css') }}" />
        <style>
            .color-preview-{{ $field['name'] }} {
                width: 40px;
            }
            .color-preview-{{ $field['name'] }} i {
                display: block;
                width: 20px;
                height: 20px;
                border-radius: 4px;
            }
        </style>
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
    <script type="text/javascript" src="{{ asset('packages/pickr/dist/pickr.min.js') }}"></script>
    <script>
        function bpFieldInitColorPickerElement(element) {
            const colorPreview = document.querySelector('.color-preview-{{ $field['name'] }} i');
            const defaultColor = element.value || '#000000';
            
            // Update color preview
            colorPreview.style.backgroundColor = defaultColor;
            
            // Initialize Pickr
            const pickrOptions = {
                el: '.color-preview-{{ $field['name'] }}',
                theme: 'classic',
                default: defaultColor,
                components: {
                    preview: true,
                    opacity: true,
                    hue: true,
                    interaction: {
                        hex: true,
                        rgba: true,
                        hsla: false,
                        hsva: false,
                        cmyk: false,
                        input: true,
                        clear: true,
                        save: true
                    }
                }
            };
            
            // Merge with any provided options
            const config = Object.assign(pickrOptions, {!! isset($field['color_picker_options']) ? json_encode($field['color_picker_options']) : '{}' !!});
            
            const pickr = Pickr.create(config);
            
            // Update input value when color changes
            pickr.on('save', (color) => {
                const colorValue = color ? color.toHEXA().toString() : '';
                element.value = colorValue;
                colorPreview.style.backgroundColor = colorValue;
                pickr.hide();
            });
            
            // Show picker on focus
            element.addEventListener('focus', () => {
                pickr.show();
            });
            
            // Update preview when input changes manually
            element.addEventListener('change', () => {
                colorPreview.style.backgroundColor = element.value;
                pickr.setColor(element.value);
            });
        }
    </script>
    @endpush
@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
