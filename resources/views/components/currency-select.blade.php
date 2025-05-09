@props(['name', 'id' => null, 'selected' => null, 'required' => false, 'label' => __('invoices.fields.payment_currency'), 'class' => ''])

<div {{ $attributes }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-base font-medium text-gray-500 mb-2 {{ $labelClass }}">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    <select 
        name="{{ $name }}" 
        id="{{ $id ?? $name }}" 
        @if($required) required @endif
        class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 {{ $class }}">
        @foreach($currencies as $code => $currency)
            <option value="{{ $code }}" {{ $selected == $code ? 'selected' : '' }}>
                {{ $code }}
            </option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
