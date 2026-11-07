{{-- A role limit (D-047): mode select plus a number used only for the limited mode; RoleLimitRequest turns the pair into the column value. --}}
@use('App\Domain\User\Enums\LimitMode')
@php
    $modeName = $field['name'] . '_mode';
    $mode = LimitMode::LIMITED->value;
    if (isset($entry)) {
        $mode = LimitMode::of($field['value'])->value;
    }
    $mode = old($modeName, $mode);
    $limit = old($field['name'], $field['value'] ?? '');
    if ($mode !== LimitMode::LIMITED->value) {
        $limit = '';
    }
@endphp
@include('crud::fields.inc.wrapper_start')
    <label for="{{ $modeName }}">{{ $field['label'] }}</label>
    <div class="d-flex gap-2">
        <select id="{{ $modeName }}" name="{{ $modeName }}" class="form-select w-auto" data-role-limit-mode
                data-limited="{{ LimitMode::LIMITED->value }}">
            @foreach (LimitMode::cases() as $option)
                <option value="{{ $option->value }}" @selected($option->value === $mode)>
                    {{ __('role_limits.statuses.' . $option->value) }}
                </option>
            @endforeach
        </select>
        <input type="number" name="{{ $field['name'] }}" value="{{ $limit }}" min="1" class="form-control w-auto"
               aria-label="{{ $field['label'] }}" @if ($mode !== LimitMode::LIMITED->value) hidden @endif>
    </div>
    @if (isset($field['hint']))
        <p class="help-block">{{ $field['hint'] }}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    @once
        @vite('resources/js/admin/role-limit-field.js')
    @endonce
@endpush
