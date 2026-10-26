@props([
    'profile',
    'countries',
])

<dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
    <div>
        <dt class="text-gray-500 dark:text-gray-400">{{ __('parties.fields.name') }}</dt>
        <dd class="text-gray-900 dark:text-white font-medium">{{ $profile->name }}</dd>
    </div>
    <div>
        <dt class="text-gray-500 dark:text-gray-400">{{ __('parties.fields.short_name') }}</dt>
        <dd class="text-gray-900 dark:text-white">{{ $profile->shortName ?? '—' }}</dd>
    </div>
    <div>
        <dt class="text-gray-500 dark:text-gray-400">{{ __('parties.fields.company_number') }}</dt>
        <dd class="text-gray-900 dark:text-white">
            @if ($profile->registrationNumber)
                {{ $profile->registrationNumber->number }}
                ({{ $countries[$profile->registrationNumber->country->value] ?? $profile->registrationNumber->country->value }})
            @else
                —
            @endif
        </dd>
    </div>
    <div>
        <dt class="text-gray-500 dark:text-gray-400">{{ __('parties.fields.vat_number') }}</dt>
        <dd class="text-gray-900 dark:text-white">{{ $profile->vatNumber?->value ?? '—' }}</dd>
    </div>
    <div>
        <dt class="text-gray-500 dark:text-gray-400">{{ __('parties.fields.email') }}</dt>
        <dd class="text-gray-900 dark:text-white">{{ $profile->email ?? '—' }}</dd>
    </div>
    <div>
        <dt class="text-gray-500 dark:text-gray-400">{{ __('parties.fields.phone') }}</dt>
        <dd class="text-gray-900 dark:text-white">{{ $profile->phone ?? '—' }}</dd>
    </div>
    <div class="md:col-span-2">
        <dt class="text-gray-500 dark:text-gray-400">{{ __('parties.sections.address') }}</dt>
        <dd class="text-gray-900 dark:text-white">
            @if ($profile->address)
                {{ $profile->address->street }}<br>
                {{ $profile->address->postalCode }} {{ $profile->address->city }}<br>
                {{ $countries[$profile->address->country->value] ?? $profile->address->country->value }}
            @else
                —
            @endif
        </dd>
    </div>
    @if ($profile->note)
        <div class="md:col-span-2">
            <dt class="text-gray-500 dark:text-gray-400">{{ __('parties.fields.note') }}</dt>
            <dd class="text-gray-900 dark:text-white whitespace-pre-line">{{ $profile->note }}</dd>
        </div>
    @endif
</dl>
