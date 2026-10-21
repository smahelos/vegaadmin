{{--
    Select2 filter (Billex implementation): picks one of $filter->values and sends its key as the
    GET parameter named after the filter; the filter logic in the CRUD controller applies it.
--}}
@php
    $isActive = $filter->isActive();
    $locale = app()->getLocale();
@endphp

<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    class="nav-item dropdown {{ $isActive ? 'active' : '' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu p-0">
        <div class="form-group backpack-filter mb-0">
            <select class="form-control"
                    data-select2-filter="{{ $filter->name }}"
                    data-placeholder="{{ $filter->placeholder ?: trans('admin.general.select') }}">
                <option value=""></option>
                @foreach ((array) $filter->values as $key => $label)
                    <option value="{{ $key }}" @selected($isActive && (string) $filter->currentValue === (string) $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</li>

@push('crud_list_styles')
    @once
        <link rel="stylesheet" type="text/css" href="{{ asset('packages/select2/dist/css/select2.min.css') }}" />
        <link rel="stylesheet" type="text/css" href="{{ asset('packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}" />
        <style>
            .navbar-filters [data-select2-filter] + .select2-container { min-width: 220px; }
        </style>
    @endonce
@endpush

@push('crud_list_scripts')
    @once
        <script src="{{ asset('packages/select2/dist/js/select2.full.min.js') }}"></script>
        @if ($locale !== 'en' && file_exists(public_path('packages/select2/dist/js/i18n/' . $locale . '.js')))
            <script src="{{ asset('packages/select2/dist/js/i18n/' . $locale . '.js') }}"></script>
        @endif
        <script>
            jQuery(function ($) {
                $('[data-select2-filter]').each(function () {
                    var select = $(this);
                    var name = select.data('select2-filter');
                    var item = $('li[filter-name="' + name + '"]');

                    select.select2({
                        theme: 'bootstrap',
                        width: '100%',
                        allowClear: true,
                        placeholder: select.data('placeholder'),
                        language: @json($locale),
                        // inside the filter dropdown, so picking an option does not close it
                        dropdownParent: select.parent(),
                    });

                    select.on('change', function () {
                        var value = select.val() || '';

                        updateDatatablesOnFilterChange(name, value, true, 0);

                        if (value) {
                            item.addClass('active');
                        } else {
                            item.removeClass('active');
                            item[0].dispatchEvent(new CustomEvent('backpack:filter:clear'));
                        }
                    });

                    item.on('shown.bs.dropdown', function () {
                        select.select2('open');
                    });

                    // "Remove filters" button of the filters navbar (it updates the URL itself):
                    // reset the select without firing the change handler above
                    item.on('backpack:filter:clear', function () {
                        item.removeClass('active');
                        select.val(null).trigger('change.select2');
                    });
                });
            });
        </script>
    @endonce
@endpush
