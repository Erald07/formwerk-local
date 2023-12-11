@props(['active'])

@php
$classes = 'cursor-pointer inline-flex items-center text-sm font-medium text-white py-1.5 px-1.5 hover:bg-gray-600 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
