<main class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <a href="/" class="inline-flex items-center"><x-brand.logo class="h-10 w-auto" /></a>
            @if ($items->isNotEmpty())
                <a href="{{ route('checkout.show') }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Finalizar compra</a>
            @endif
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-700">Sua seleção</p>
                    <h1 class="mt-2 text-2xl font-bold text-slate-950">Carrinho</h1>
                    <p class="mt-2 text-sm text-slate-500">Revise as fotos antes do pagamento. Os arquivos originais são liberados após a aprovação.</p>
                </div>
                @if ($items->isNotEmpty())
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $items->count() }} foto(s)</span>
                @endif
            </div>
            <div class="mt-6 divide-y divide-slate-100">
                @forelse ($items as $item)
                    <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            <img src="{{ route('media.photos.thumbnail', $item['photo']) }}" class="h-20 w-28 rounded-xl object-cover" alt="{{ $item['photo']->filename }}">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $item['photo']->event->name }}</p>
                                <p class="text-sm text-slate-500">{{ $item['photo']->filename }}</p>
                            </div>
                        </div>
                        <div class="sm:text-right">
                            <p class="font-semibold">R$ {{ number_format($item['price'], 2, ',', '.') }}</p>
                            <button wire:click="remove_photo('{{ $item['photo']->public_id }}')" class="mt-2 text-sm font-semibold text-red-600">Remover</button>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <h2 class="text-xl font-bold text-slate-950">Seu carrinho está vazio</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Volte para a galeria do evento, encontre suas fotos e adicione as favoritas aqui.</p>
                    </div>
                @endforelse
            </div>
            @if ($items->isNotEmpty())
            <div class="mt-6 space-y-2 border-t border-slate-100 pt-5 text-right">
                <p class="text-sm text-slate-500">Subtotal: R$ {{ number_format($summary['subtotal'], 2, ',', '.') }}</p>
                @if ($summary['discount_amount'] > 0)
                    <p class="text-sm font-semibold text-emerald-700">Desconto por volume: -R$ {{ number_format($summary['discount_amount'], 2, ',', '.') }} ({{ number_format($summary['discount_percent'] * 100, 0) }}%)</p>
                @else
                    <p class="text-sm text-slate-500">Adicione 3 fotos ou mais para liberar desconto por volume.</p>
                @endif
                <p class="text-xl font-bold text-slate-950">Total: R$ {{ number_format($total, 2, ',', '.') }}</p>
            </div>
            <div class="mt-6 flex justify-end">
                <a href="{{ route('checkout.show') }}" class="rounded-2xl bg-emerald-600 px-6 py-4 text-sm font-bold text-white">Ir para pagamento</a>
            </div>
            @endif
        </div>
    </div>
</main>
