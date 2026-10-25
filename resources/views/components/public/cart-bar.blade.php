@props(['count' => 0, 'total' => 0, 'discountPercent' => 0, 'nextDiscount' => null])

@if ($count > 0)
    <div class="fixed inset-x-0 bottom-0 z-40 p-3 sm:p-4" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 rounded-2xl bg-slate-950 px-4 py-3 text-white shadow-2xl shadow-slate-950/30 sm:px-5">
            <div class="min-w-0">
                <p class="text-sm font-semibold">
                    {{ $count }} {{ $count === 1 ? 'foto' : 'fotos' }} · R$ {{ number_format($total, 2, ',', '.') }}
                </p>
                <p class="truncate text-xs text-emerald-300">
                    @if ($nextDiscount)
                        Mais {{ $nextDiscount['missing_photos'] }} {{ $nextDiscount['missing_photos'] === 1 ? 'foto' : 'fotos' }} e você ganha {{ number_format($nextDiscount['percent'] * 100, 0) }}% de desconto
                    @elseif ($discountPercent > 0)
                        Desconto de {{ number_format($discountPercent * 100, 0) }}% aplicado
                    @endif
                </p>
            </div>
            <a href="{{ route('checkout.show') }}" class="shrink-0 rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-emerald-300">Finalizar compra</a>
        </div>
    </div>
@endif
