<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-red-500 hover:shadow-lg hover:shadow-red-900/10 focus:outline-none']) }}>
    {{ $slot }}
</button>
