@props(['active'])

@php
$classes = ($active ?? false)
            ? 'mx-3 block rounded-2xl bg-slate-950 px-4 py-3 text-start text-base font-semibold text-white transition duration-150 ease-in-out focus:outline-none'
            : 'mx-3 block rounded-2xl px-4 py-3 text-start text-base font-semibold text-slate-600 transition duration-150 ease-in-out hover:bg-white hover:text-slate-950 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
