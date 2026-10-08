<div>
    <x-public.header :event="$event" />

    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
        @if ($event)
            <a href="{{ route('public.events.show', $event->slug) }}#buscar" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-950">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
                Continuar escolhendo fotos
            </a>
        @endif

        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950">Finalizar compra</h1>

        @if ($items->isEmpty())
            <div class="fotx-card mt-6 p-10 text-center">
                <h2 class="text-xl font-bold text-slate-950">Seu carrinho está vazio</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Volte para a galeria do evento, encontre suas fotos e adicione as favoritas.</p>
                <button type="button" onclick="history.back()" class="fotx-button-secondary mt-6">Voltar</button>
            </div>
        @else
            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_380px] lg:gap-8">
                <section class="fotx-card self-start p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-bold text-slate-950">Suas fotos</h2>
                        <span class="fotx-chip">{{ $items->count() }} {{ $items->count() === 1 ? 'foto' : 'fotos' }}</span>
                    </div>

                    @if ($next_discount)
                        <p class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-100">
                            Adicione mais {{ $next_discount['missing_photos'] }} {{ $next_discount['missing_photos'] === 1 ? 'foto' : 'fotos' }} e ganhe {{ number_format($next_discount['percent'] * 100, 0) }}% de desconto no pedido.
                        </p>
                    @endif

                    <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($items as $item)
                            <li wire:key="item-{{ $item['photo']->public_id }}" class="overflow-hidden rounded-2xl bg-slate-50 ring-1 ring-slate-200/80">
                                <div class="aspect-[4/3] bg-slate-100">
                                    <img src="{{ route('media.photos.thumbnail', $item['photo']) }}" loading="lazy" class="h-full w-full object-contain" alt="Foto do evento {{ $item['photo']->event->name }}">
                                </div>
                                <div class="flex items-center justify-between gap-2 px-3 py-2">
                                    <span class="text-sm font-semibold text-slate-900">R$ {{ number_format($item['price'], 2, ',', '.') }}</span>
                                    <button type="button" wire:click="remove_photo('{{ $item['photo']->public_id }}')" class="text-xs font-semibold text-slate-500 hover:text-red-600">Remover</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <form wire:submit="start_payment" class="fotx-card self-start p-5 sm:p-6 lg:sticky lg:top-24">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between text-slate-500"><dt>Subtotal</dt><dd>R$ {{ number_format($summary['subtotal'], 2, ',', '.') }}</dd></div>
                        @if ($summary['discount_amount'] > 0)
                            <div class="flex justify-between font-semibold text-emerald-700"><dt>Desconto por volume ({{ number_format($summary['discount_percent'] * 100, 0) }}%)</dt><dd>-R$ {{ number_format($summary['discount_amount'], 2, ',', '.') }}</dd></div>
                        @endif
                        <div class="flex justify-between border-t border-slate-100 pt-3 text-xl font-bold text-slate-950"><dt>Total</dt><dd>R$ {{ number_format($total, 2, ',', '.') }}</dd></div>
                    </dl>

                    <div class="mt-6 space-y-4">
                        <div>
                            <label for="buyer_email" class="text-sm font-semibold text-slate-800">E-mail</label>
                            <input id="buyer_email" type="email" wire:model="buyer_email" autocomplete="email" placeholder="voce@email.com" class="fotx-input mt-2 w-full px-4 py-3" />
                            <p class="mt-1 text-xs text-slate-500">Ele identifica seu pedido. Com uma conta Fotx no mesmo e-mail, suas compras ficam em Minhas fotos.</p>
                            @error('buyer_email') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="buyer_name" class="text-sm font-semibold text-slate-800">Nome <span class="font-normal text-slate-400">(opcional)</span></label>
                            <input id="buyer_name" wire:model="buyer_name" autocomplete="name" placeholder="Como devemos chamar você?" class="fotx-input mt-2 w-full px-4 py-3" />
                        </div>

                        @error('payment') <p class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p> @enderror

                        <button class="w-full rounded-full bg-emerald-500 px-5 py-4 text-sm font-bold text-slate-950 transition hover:bg-emerald-400 disabled:opacity-50" wire:loading.attr="disabled" wire:target="start_payment">
                            <span wire:loading.remove wire:target="start_payment">Ir para o pagamento</span>
                            <span wire:loading wire:target="start_payment">Abrindo o pagamento...</span>
                        </button>
                    </div>

                    <ul class="mt-5 space-y-2 text-xs text-slate-500">
                        <li class="flex items-center gap-2"><svg class="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3Z"/></svg>Pagamento processado pelo Mercado Pago</li>
                        <li class="flex items-center gap-2"><svg class="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 4v12m0 0-4-4m4 4 4-4"/></svg>Fotos originais em alta resolução, sem marca d'água</li>
                        <li class="flex items-center gap-2"><svg class="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Download liberado assim que o pagamento é aprovado</li>
                    </ul>
                </form>
            </div>
        @endif
    </main>
</div>
