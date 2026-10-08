<a href="{{ route('checkout.show') }}" class="relative inline-flex items-center gap-2 rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" aria-label="Carrinho com {{ $cart_count }} foto(s)">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2"/><circle cx="10" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
    <span>Carrinho</span>
    @if ($cart_count > 0)
        <span class="inline-flex min-w-5 justify-center rounded-full bg-emerald-400 px-1.5 text-xs font-bold text-slate-950">{{ $cart_count }}</span>
    @endif
</a>
