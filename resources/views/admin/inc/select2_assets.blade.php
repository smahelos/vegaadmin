{{--
    Shared select2 assets and initializers for Billex's own select2-based fields
    (select2, select2_from_array, select2_from_ajax, relationship). Backpack calls the function named
    in the field's data-init-function attribute once per field (form_content.blade.php).
--}}
@php
    $select2Locale = app()->getLocale();
@endphp

@push('crud_fields_styles')
    @once
        <link rel="stylesheet" type="text/css" href="{{ asset('packages/select2/dist/css/select2.min.css') }}" />
        <link rel="stylesheet" type="text/css" href="{{ asset('packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}" />
        <style>
            /* the bootstrap theme paints a disabled select2 light grey; match Backpack's disabled inputs */
            [data-bs-theme=dark] .select2-container--bootstrap.select2-container--disabled .select2-selection {
                background-color: var(--bp-form-disabled-bg);
            }
            [data-bs-theme=dark] .select2-container--bootstrap.select2-container--disabled .select2-selection__rendered {
                color: var(--bp-form-disabled-color);
            }
        </style>
    @endonce
@endpush

@push('crud_fields_scripts')
    @once
        <script src="{{ asset('packages/select2/dist/js/select2.full.min.js') }}"></script>
        @if ($select2Locale !== 'en' && file_exists(public_path('packages/select2/dist/js/i18n/' . $select2Locale . '.js')))
            <script src="{{ asset('packages/select2/dist/js/i18n/' . $select2Locale . '.js') }}"></script>
        @endif
        <script>
            function billexSelect2Options(element) {
                return {
                    theme: 'bootstrap',
                    width: '100%',
                    language: @json($select2Locale),
                    placeholder: element.data('placeholder') || '',
                    allowClear: element.data('allows-null') === true && !element.prop('multiple'),
                };
            }

            function bpFieldInitBillexSelect2(element) {
                element.select2(billexSelect2Options(element));
            }

            // Search endpoint: GET data-source?q=<term>&page=<n>, answering either a plain array of records
            // or a Laravel paginator ({data, current_page, last_page}).
            function bpFieldInitBillexSelect2Ajax(element) {
                var key = element.data('key');
                var attribute = element.data('attribute');

                element.select2($.extend(billexSelect2Options(element), {
                    minimumInputLength: element.data('minimum-input-length') || 0,
                    ajax: {
                        url: element.data('source'),
                        dataType: 'json',
                        delay: 400,
                        data: function (params) {
                            return { q: params.term, page: params.page || 1 };
                        },
                        processResults: function (data, params) {
                            var paginated = !Array.isArray(data);
                            var records = paginated ? (data.data || []) : data;
                            var term = (params.term || '').toLowerCase();

                            return {
                                results: records
                                    .map(function (record) {
                                        return { id: record[key], text: String(record[attribute] ?? '') };
                                    })
                                    // a plain array is the whole list: narrow it to the term here
                                    .filter(function (option) {
                                        return paginated || !term || option.text.toLowerCase().indexOf(term) !== -1;
                                    }),
                                pagination: { more: paginated && data.current_page < data.last_page },
                            };
                        },
                    },
                }));
            }
        </script>
    @endonce
@endpush
