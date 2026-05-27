@props(['variant' => 'dark'])

@php
    $src = $variant === 'light'
        ? asset('brand/fotx-logo-white.png')
        : asset('brand/fotx-logo-black.png');
@endphp

<img src="{{ $src }}" alt="Fotx" {{ $attributes->merge(['class' => 'h-8 w-auto']) }}>
