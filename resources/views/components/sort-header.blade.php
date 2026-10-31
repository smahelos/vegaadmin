@props([
    'key',
    'label',
    'orderBy',
    'orderAsc',
])

<th scope="col" {{ $attributes->merge(['class' => 'px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider']) }}>
    <button type="button" class="flex items-center cursor-pointer uppercase" wire:click="sortBy('{{ $key }}')">
        {{ $label }}
        <span class="ml-1">
            @if ($orderBy !== $key)
                <i class="fas fa-sort text-gray-400"></i>
            @elseif ($orderAsc)
                <i class="fas fa-sort-up text-green-600"></i>
            @else
                <i class="fas fa-sort-down text-green-600"></i>
            @endif
        </span>
    </button>
</th>
