@props(['name', 'id' => null, 'selected' => null, 'required' => false, 'label' => __('invoices.fields.payment_currency'), 'class' => ''])

<div {{ $attributes }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-base font-medium text-gray-900 dark:text-white mb-2 {{ $labelClass }}">
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
        class="form-select block w-full rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base bg-blue-50 dark:bg-gray-700 dark:text-gray-200 py-2 {{ $class }}">
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
