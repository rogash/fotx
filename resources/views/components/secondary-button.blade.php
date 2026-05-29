<button {{ $attributes->merge(['type' => 'button', 'class' => 'fotx-button-secondary disabled:opacity-40']) }}>
    {{ $slot }}
</button>
