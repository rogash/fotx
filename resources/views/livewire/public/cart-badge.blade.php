<a href="{{ route('cart.show') }}" class="inline-flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15">
    <span>Carrinho</span>
    @if ($cart_count > 0)
        <span class="inline-flex min-w-6 justify-center rounded-full bg-emerald-400 px-2 py-0.5 text-xs font-bold text-slate-950">{{ $cart_count }}</span>
    @endif
</a>
