@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'fotx-input px-4 py-3']) }}>
