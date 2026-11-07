{{--
    Entity usage column (Billex implementation): a user's usage of each plan limit, read-only.
    The value is a list of EntityUsageDTO, or null when the user holds several plan roles.
--}}
@push('after_styles')
    @once
        <style>
            /* Tabler paints the value of a native progress element with --tblr-primary */
            .entity-usage-ok { --tblr-primary: var(--tblr-success); }
            .entity-usage-near { --tblr-primary: var(--tblr-warning); }
            .entity-usage-full { --tblr-primary: var(--tblr-danger); }
        </style>
    @endonce
@endpush

@if ($column['value'] === null)
    <span class="text-danger">{{ __('role_limits.usage.ambiguous') }}</span>
@else
    <table class="table table-sm mb-0">
        <tbody>
            @foreach ($column['value'] as $usage)
                <tr>
                    <th id="usage-{{ $usage->entity->value }}" scope="row" class="fw-normal">
                        {{ __('role_limits.usage.' . $usage->entity->value) }}
                    </th>
                    <td class="text-end text-nowrap">
                        {{ $usage->used }} /
                        @if ($usage->limit === null)
                            {{ __('role_limits.statuses.unlimited') }}
                        @elseif ($usage->limit === 0)
                            {{ __('role_limits.statuses.not_allowed') }}
                        @else
                            {{ $usage->limit }}
                        @endif
                    </td>
                    <td class="w-50 align-middle">
                        @if ($usage->limit > 0)
                            <progress aria-labelledby="usage-{{ $usage->entity->value }}" max="{{ $usage->limit }}" value="{{ $usage->used }}"
                                @class([
                                    'progress',
                                    'entity-usage-full' => $usage->full(),
                                    'entity-usage-near' => $usage->near(),
                                    'entity-usage-ok' => !$usage->full() && !$usage->near(),
                                ])></progress>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
