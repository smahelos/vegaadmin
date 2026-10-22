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
    class="nav-item dropdown {{ $isActive ? 'active' : '' }}"
    filter-init-function="billexInitSelect2Filter">
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
            // Called by the filters navbar on load and again after "Remove filters"
            function billexInitSelect2Filter(filter, navbar) {
                if (filter.dataset.billexInitialized) {
                    return;
                }
                filter.dataset.billexInitialized = '1';

                var item = $(filter);
                var name = item.attr('filter-name');
                var select = item.find('[data-select2-filter]');

                select.select2({
                    theme: 'bootstrap',
                    width: '100%',
                    allowClear: true,
                    placeholder: select.data('placeholder'),
                    language: @json($locale),
                    // keeps the select2 list inside the filter menu instead of the page body
                    dropdownParent: select.parent(),
                });

                select.on('change', function () {
                    var value = select.val() || '';

                    bootstrap.Dropdown.getOrCreateInstance(item.find('.dropdown-toggle')[0]).hide();
                    item.toggleClass('active', value !== '');
                    document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
                        detail: {
                            filterName: name,
                            filterValue: value,
                            shouldUpdateUrl: true,
                            debounce: 0,
                            componentId: navbar.getAttribute('data-component-id') || '',
                        },
                    }));
                });

                item.on('shown.bs.dropdown', function () {
                    select.select2('open');
                });

                // the select2 list lives inside the filter menu: it must not open (select2 reopens it
                // after the × clears the value) or stay open while the menu is hidden
                select.on('select2:opening', function (e) {
                    if (!item.find('.dropdown-menu').hasClass('show')) {
                        e.preventDefault();
                    }
                });

                item.on('hidden.bs.dropdown', function () {
                    select.select2('close');
                });

                // "Remove filters" button of the filters navbar (it updates the URL itself):
                // reset the select without firing the change handler above
                filter.addEventListener('backpack:filter:clear', function () {
                    item.removeClass('active');
                    select.val(null).trigger('change.select2');
                });
            }
        </script>
    @endonce
@endpush
