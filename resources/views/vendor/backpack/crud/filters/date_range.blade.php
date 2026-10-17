{{--
    Date range filter (Billex implementation on bootstrap-daterangepicker + moment).
    Sends {"from": "Y-m-d H:i:s", "to": "Y-m-d H:i:s"} as the GET parameter named after the filter;
    the filter logic in the CRUD controller applies it.
--}}
@php
    $currentRange = $filter->currentValue ? json_decode($filter->currentValue, true) : null;
    $locale = str_replace('_', '-', app()->getLocale());
@endphp

<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    class="nav-item dropdown {{ $currentRange ? 'active' : '' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu p-0">
        <div class="form-group backpack-filter mb-0">
            <div class="input-group date">
                <span class="input-group-text"><i class="la la-calendar"></i></span>
                <input class="form-control"
                       type="text"
                       autocomplete="off"
                       data-date-range-filter="{{ $filter->name }}"
                       data-locale="{{ $locale }}"
                       data-from="{{ $currentRange['from'] ?? '' }}"
                       data-to="{{ $currentRange['to'] ?? '' }}">
                <a class="input-group-text" href="#" data-date-range-clear="{{ $filter->name }}" title="{{ trans('backpack::crud.clear') }}"><i class="la la-times"></i></a>
            </div>
        </div>
    </div>
</li>

@push('crud_list_styles')
    @once
        <link rel="stylesheet" type="text/css" href="{{ asset('packages/bootstrap-daterangepicker/daterangepicker.css') }}" />
        <style>
            .navbar-filters .input-group.date { width: 320px; max-width: 100%; }
            .daterangepicker { z-index: 3001 !important; }
        </style>
    @endonce
@endpush

@push('crud_list_scripts')
    @once
        <script src="{{ asset('packages/moment/min/moment.min.js') }}"></script>
        <script src="{{ asset('packages/moment/min/locales.min.js') }}"></script>
        <script src="{{ asset('packages/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
        <script>
            jQuery(function ($) {
                var labels = {
                    apply: @json(trans('backpack::crud.apply')),
                    cancel: @json(trans('backpack::crud.cancel')),
                    customRange: @json(trans('backpack::crud.custom_range')),
                };

                $('[data-date-range-filter]').each(function () {
                    var input = $(this);
                    var name = input.data('date-range-filter');
                    var item = $('li[filter-name="' + name + '"]');
                    var serverFormat = 'YYYY-MM-DD HH:mm:ss';

                    moment.locale(input.data('locale'));
                    var displayFormat = moment.localeData().longDateFormat('L');

                    var from = input.data('from') ? moment(input.data('from'), serverFormat) : null;
                    var to = input.data('to') ? moment(input.data('to'), serverFormat) : null;

                    input.daterangepicker({
                        autoUpdateInput: false,
                        startDate: from || moment().startOf('month'),
                        endDate: to || moment().endOf('month'),
                        locale: {
                            format: displayFormat,
                            applyLabel: labels.apply,
                            cancelLabel: labels.cancel,
                            customRangeLabel: labels.customRange,
                            firstDay: moment.localeData().firstDayOfWeek(),
                        },
                    });

                    function showRange(start, end) {
                        input.val(start && end ? start.format(displayFormat) + ' – ' + end.format(displayFormat) : '');
                    }

                    function applyRange(start, end) {
                        var value = start && end
                            ? JSON.stringify({ from: start.startOf('day').format(serverFormat), to: end.endOf('day').format(serverFormat) })
                            : '';

                        showRange(start, end);
                        updateDatatablesOnFilterChange(name, value, true, 0);

                        if (value) {
                            item.addClass('active');
                        } else {
                            item.removeClass('active').trigger('filter:clear');
                        }
                    }

                    showRange(from, to);

                    input.on('apply.daterangepicker', function (e, picker) {
                        applyRange(picker.startDate, picker.endDate);
                    });

                    // The picker is appended to <body>: clicks inside it must not close the filter dropdown,
                    // otherwise the input gets hidden and the picker is repositioned to the page corner.
                    item.on('hide.bs.dropdown', function () {
                        if (input.data('daterangepicker').isShowing) {
                            return false;
                        }
                    });
                    item.on('hidden.bs.dropdown', function () {
                        input.data('daterangepicker').hide();
                    });
                    item.on('shown.bs.dropdown', function () {
                        input.trigger('focus');
                    });

                    $('[data-date-range-clear="' + name + '"]').on('click', function (e) {
                        e.preventDefault();
                        applyRange(null, null);
                    });

                    // Clear link and the "Remove filters" button of the filters navbar: also reset the picker's
                    // selection, otherwise Apply would bring the cleared range back.
                    item.on('filter:clear', function () {
                        var picker = input.data('daterangepicker');
                        picker.setStartDate(moment().startOf('month'));
                        picker.setEndDate(moment().endOf('month'));
                        item.removeClass('active');
                        showRange(null, null);
                    });
                });
            });
        </script>
    @endonce
@endpush
