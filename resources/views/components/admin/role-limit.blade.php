@use('App\Domain\User\Enums\LimitMode')
<label for="{{ $modeName }}">{{ $field['label'] }}</label>
<div class="d-flex gap-2">
    <select id="{{ $modeName }}" name="{{ $modeName }}" class="form-select w-auto" data-role-limit-mode
            data-limited="{{ LimitMode::LIMITED->value }}">
        @foreach (LimitMode::cases() as $option)
            <option value="{{ $option->value }}" @selected($option === $mode)>
                {{ __('role_limits.statuses.' . $option->value) }}
            </option>
        @endforeach
    </select>
    <input type="number" name="{{ $field['name'] }}" value="{{ $limit }}" min="1" class="form-control w-auto"
           aria-label="{{ $field['label'] }}" @if ($mode !== LimitMode::LIMITED) hidden @endif>
</div>
@if (isset($field['hint']))
    <p class="help-block">{{ $field['hint'] }}</p>
@endif
