{{--
    Shared air-datepicker assets for Billex's own datepicker field and datepicker_range filter.
    $stack is the Backpack stack prefix of the page: 'crud_fields' (forms) or 'crud_list' (list filters).
    The locale (names, formats, first day of the week) comes from lang/{locale}/admin.php, key "datepicker".
--}}
@push($stack . '_styles')
    @once
        @basset('https://cdn.jsdelivr.net/npm/air-datepicker@3.6.0/air-datepicker.css')
        <style>
            /* Tabler colours, defined for both themes, so the picker follows the light/dark switch (data-bs-theme) */
            .air-datepicker {
                --adp-font-family: inherit;
                --adp-border-radius: var(--tblr-border-radius);
                --adp-cell-border-radius: var(--tblr-border-radius);
                --adp-background-color: var(--tblr-bg-surface);
                --adp-background-color-hover: var(--tblr-active-bg);
                --adp-background-color-active: var(--tblr-active-bg);
                --adp-background-color-in-range: rgba(var(--tblr-primary-rgb), .1);
                --adp-background-color-in-range-focused: rgba(var(--tblr-primary-rgb), .2);
                --adp-background-color-selected-other-month: rgba(var(--tblr-primary-rgb), .6);
                --adp-background-color-selected-other-month-focused: rgba(var(--tblr-primary-rgb), .7);
                --adp-border-color: var(--tblr-border-color);
                --adp-border-color-inner: var(--tblr-border-color);
                --adp-border-color-inline: var(--tblr-border-color);
                --adp-color: var(--tblr-body-color);
                --adp-color-secondary: var(--tblr-secondary);
                --adp-color-other-month: var(--tblr-disabled-color);
                --adp-color-other-month-hover: var(--tblr-secondary);
                --adp-color-disabled: var(--tblr-disabled-color);
                --adp-color-disabled-in-range: var(--tblr-disabled-color);
                --adp-accent-color: var(--tblr-primary);
                --adp-day-name-color: var(--tblr-secondary);
                --adp-day-name-color-hover: var(--tblr-primary);
                --adp-cell-background-color-selected: var(--tblr-primary);
                --adp-cell-background-color-selected-hover: rgba(var(--tblr-primary-rgb), .85);
                --adp-cell-background-color-in-range: rgba(var(--tblr-primary-rgb), .1);
                --adp-cell-background-color-in-range-hover: rgba(var(--tblr-primary-rgb), .2);
                --adp-time-track-color: var(--tblr-secondary);
                --adp-time-track-color-hover: var(--tblr-body-color);
            }
            /* #fff in the library; separate rules, a browser drops a whole selector list with an unknown pseudo-element */
            .air-datepicker--pointer:after { background: var(--adp-background-color); }
            .air-datepicker-time--row input[type=range]::-webkit-slider-thumb { background: var(--adp-background-color); }
            .air-datepicker-time--row input[type=range]::-moz-range-thumb { background: var(--adp-background-color); }
        </style>
    @endonce
@endpush

@push($stack . '_scripts')
    @once
        @basset('https://cdn.jsdelivr.net/npm/air-datepicker@3.6.0/air-datepicker.js')
        <script>
            window.billexDatepicker = {
                locale: @json(trans('admin.datepicker')),

                // Server value ("Y-m-d", "Y-m-d H:i[:s]" or "H:i[:s]") to a local Date, null when empty or invalid.
                parse: function (value) {
                    var date = /(\d{4})-(\d{2})-(\d{2})/.exec(value || '');
                    var time = /(\d{2}):(\d{2})/.exec(value || '');

                    if (!date && !time) {
                        return null;
                    }

                    var result = date ? new Date(+date[1], date[2] - 1, +date[3]) : new Date();
                    result.setHours(time ? +time[1] : 0, time ? +time[2] : 0, 0, 0);

                    return result;
                },
            };
        </script>
    @endonce
@endpush
