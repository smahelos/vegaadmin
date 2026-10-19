{{--
    Datepicker field (Billex implementation on air-datepicker): a date, a date and time, or a time only.
    'datepicker_options' are air-datepicker options passed as they are; they also pick the mode:
    none → date ("Y-m-d"), 'timepicker' => true → date and time ("Y-m-d H:i"), 'onlyTimepicker' => true → time ("H:i").
    The visible input shows the admin locale's format, a hidden input submits the server value.
    A readonly or disabled input only shows the value.
--}}
@php
    $options = $field['datepicker_options'] ?? [];
    $timeOnly = ! empty($options['onlyTimepicker']);
    $serverFormat = match (true) {
        $timeOnly => 'H:i',
        ! empty($options['timepicker']) => 'Y-m-d H:i',
        default => 'Y-m-d',
    };

    $value = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';
    $timestamp = $value instanceof \DateTimeInterface ? $value->getTimestamp() : strtotime((string) $value);
    $value = $timestamp ? date($serverFormat, $timestamp) : '';
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <input type="hidden" name="{{ $field['name'] }}" value="{{ $value }}" data-datepicker-value>
    <div class="input-group">
        <input
            type="text"
            autocomplete="off"
            data-init-function="bpFieldInitDatepicker"
            data-datepicker-options="{{ json_encode((object) $options) }}"
            @include('crud::fields.inc.attributes')
        >
        <span class="input-group-text"><i class="la {{ $timeOnly ? 'la-clock' : 'la-calendar' }}"></i></span>
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@include('admin.inc.datepicker_assets', ['stack' => 'crud_fields'])

@push('crud_fields_scripts')
    @once
        <script>
            function bpFieldInitDatepicker(element) {
                var hidden = element.closest('[bp-field-wrapper]').find('[data-datepicker-value]');
                var options = element.data('datepicker-options');
                var serverFormat = options.onlyTimepicker ? 'HH:mm' : (options.timepicker ? 'yyyy-MM-dd HH:mm' : 'yyyy-MM-dd');
                var selected = window.billexDatepicker.parse(hidden.val());
                var readonly = element.is('[readonly], [disabled]');

                var datepicker = new AirDatepicker(element[0], Object.assign({
                    locale: window.billexDatepicker.locale,
                    selectedDates: selected ? [selected] : [],
                    timepicker: !!options.onlyTimepicker, // air-datepicker shows the time only with both options
                    autoClose: !options.timepicker && !options.onlyTimepicker,
                    buttons: ['clear'],
                    // readonly/disabled: never open the calendar (no event of this name is ever dispatched)
                    showEvent: readonly ? 'datepicker:never' : 'focus',
                    onSelect: function (event) {
                        hidden.val(event.date ? event.datepicker.formatDate(event.date, serverFormat) : '').trigger('change');
                    },
                }, options));

                // Typed text is not parsed, so typing is blocked; Backspace and Delete clear the value.
                element.on('keydown', function (e) {
                    if (readonly) {
                        return;
                    }
                    if (e.key === 'Backspace' || e.key === 'Delete') {
                        e.preventDefault();
                        datepicker.clear();
                    } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
                        e.preventDefault();
                    }
                });
                element.on('paste drop', function (e) {
                    e.preventDefault();
                });
            }
        </script>
    @endonce
@endpush
