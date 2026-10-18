{{--
    Relationship table column (Billex implementation): the related records of a "many" relation as a table.
    Options: columns ([attribute => label]), raw (attributes rendered as HTML, e.g. accessors returning markup).
    Boolean values are shown as yes/no.
--}}
@php
    $related = data_get($entry, $column['entity'] ?? $column['name']) ?? collect();
    $raw = $column['raw'] ?? [];
@endphp

@if ($related->isEmpty())
    <span>{{ $column['default'] ?? '-' }}</span>
@else
    <table class="table table-sm table-striped mb-0">
        <thead>
            <tr>
                @foreach ($column['columns'] as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($related as $record)
                <tr>
                    @foreach (array_keys($column['columns']) as $attribute)
                        @php $value = $record->{$attribute}; @endphp
                        <td>
                            @if (is_bool($value))
                                {{ $value ? trans('admin.general.yes') : trans('admin.general.no') }}
                            @elseif (in_array($attribute, $raw, true))
                                {!! $value !!}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
