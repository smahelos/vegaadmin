@include('crud::fields.inc.wrapper_start')
    <label>{{ $field['label'] }}</label>
    @if (!empty($field['value']))
        <div class="mb-3">
            <img src="{{ asset('storage/' . $field['value']) }}" alt="{{ __('parties.fields.logo') }}" class="img-fluid" width="160">
        </div>
    @endif
    <input type="file" name="{{ $field['name'] }}" accept="image/png,image/jpeg,image/webp" class="form-control">
    <p class="help-block">{{ __('parties.hints.logo') }}</p>
@include('crud::fields.inc.wrapper_end')
