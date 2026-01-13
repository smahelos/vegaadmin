@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])

@php
switch ($align) {
case 'left':
$alignmentClasses = 'origin-top-left left-0';
break;
case 'top':
$alignmentClasses = 'origin-top';
break;
case 'right':
default:
$alignmentClasses = 'origin-top-right right-0';
break;
}

$width = 'w-' . $width;
@endphp

<div class="relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-2 {{ $width }} rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 {{ $alignmentClasses }}"
        style="display: none;" @click="open = false">
        <div class="rounded-sm bg-white dark:bg-gray-800 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
