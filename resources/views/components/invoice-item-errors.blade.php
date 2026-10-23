{{-- Validation errors of the invoice items; the items travel as one hidden JSON field, so no input shows them. --}}
@php
    $messages = array_unique(array_merge(
        $errors->get('invoice-products'),
        ...array_values($errors->get('invoice-products.*'))
    ));
@endphp
@if ($messages)
    <ul class="mt-2 space-y-1 text-sm text-red-600" role="alert">
        @foreach ($messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
