@foreach($units as $unit)
<option value="{{ $unit->value }}" @selected($unit === $selected)>{{ __('invoices.units.' . $unit->value) }}</option>
@endforeach
