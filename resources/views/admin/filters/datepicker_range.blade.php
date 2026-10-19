{{--
    Date range filter (Billex implementation on air-datepicker): an inline range calendar in the filter dropdown.
    Sends {"from": "Y-m-d 00:00:00", "to": "Y-m-d 23:59:59"} as the GET parameter named after the filter;
    the filter logic in the CRUD controller applies it.
--}}
@php
    $currentRange = $filter->currentValue ? json_decode($filter->currentValue, true) : null;
@endphp

<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    class="nav-item dropdown {{ $currentRange ? 'active' : '' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu p-2">
        <div class="input-group mb-2">
            <input class="form-control" type="text" readonly data-datepicker-range-text>
            <a class="input-group-text" href="#" data-datepicker-range-clear title="{{ trans('backpack::crud.clear') }}"><i class="la la-times"></i></a>
        </div>
        <div data-datepicker-range-filter="{{ $filter->name }}"
             data-from="{{ $currentRange['from'] ?? '' }}"
             data-to="{{ $currentRange['to'] ?? '' }}"></div>
    </div>
</li>

@include('admin.fields.datepicker_assets', ['stack' => 'crud_list'])

@push('crud_list_scripts')
    @once
        <script>
            jQuery(function ($) {
                $('[data-datepicker-range-filter]').each(function () {
                    var calendar = $(this);
                    var name = calendar.data('datepicker-range-filter');
                    var item = $('li[filter-name="' + name + '"]');
                    var text = item.find('[data-datepicker-range-text]');
                    var locale = window.billexDatepicker.locale;
                    var from = window.billexDatepicker.parse(calendar.data('from'));
                    var to = window.billexDatepicker.parse(calendar.data('to'));

                    function showRange(dates) {
                        text.val(dates.length === 2 ? dates.map(function (date) {
                            return datepicker.formatDate(date, locale.dateFormat);
                        }).join(' – ') : '');
                    }

                    function applyRange(dates) {
                        var value = dates.length === 2
                            ? JSON.stringify({
                                from: datepicker.formatDate(dates[0], 'yyyy-MM-dd') + ' 00:00:00',
                                to: datepicker.formatDate(dates[1], 'yyyy-MM-dd') + ' 23:59:59',
                            })
                            : '';

                        showRange(dates);
                        updateDatatablesOnFilterChange(name, value, true, 0);

                        if (value) {
                            item.addClass('active');
                        } else {
                            item.removeClass('active').trigger('filter:clear');
                        }
                    }

                    var datepicker = new AirDatepicker(this, {
                        inline: true,
                        range: true,
                        locale: locale,
                        selectedDates: from && to ? [from, to] : [],
                        startDate: from || new Date(),
                        onSelect: function (event) {
                            // the first click only starts the range
                            if (event.date.length !== 1) {
                                applyRange(event.date);
                            }
                        },
                    });

                    showRange(datepicker.selectedDates);

                    item.find('[data-datepicker-range-clear]').on('click', function (e) {
                        e.preventDefault();
                        datepicker.clear();
                    });

                    // "Remove filters" button of the filters navbar (it updates the URL itself)
                    item.on('filter:clear', function () {
                        if (datepicker.selectedDates.length) {
                            datepicker.clear({ silent: true });
                        }
                        item.removeClass('active');
                        showRange([]);
                    });
                });
            });
        </script>
    @endonce
@endpush
