<div>
    <x-public.header :event="$event" />

    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
        <a href="{{ route('public.events.show', $event->slug) }}#buscar" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-950">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
            Voltar às fotos
        </a>

        <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_360px] lg:gap-8">
            <section class="overflow-hidden rounded-3xl bg-white p-2 shadow-sm ring-1 ring-slate-200/80">
                <div class="flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-2xl bg-slate-100">
                    <img src="{{ route('media.photos.watermarked', $event_photo) }}" class="h-full w-full object-contain" alt="Foto do evento {{ $event->name }}">
                </div>
            </section>

            <aside class="fotx-card self-start p-6">
                <p class="text-sm font-semibold text-emerald-700">{{ $event->name }}</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-950">Foto do evento</h1>

                @if ($event_photo->participant_code || $event_photo->search_keywords)
                    <dl class="mt-4 space-y-1 text-sm text-slate-600">
                        @if ($event_photo->participant_code)
                            <div><dt class="inline font-semibold text-slate-800">Número:</dt> <dd class="inline">{{ $event_photo->participant_code }}</dd></div>
                        @endif
                        @if ($event_photo->search_keywords)
                            <div><dt class="inline font-semibold text-slate-800">Tags:</dt> <dd class="inline">{{ $event_photo->search_keywords }}</dd></div>
                        @endif
                    </dl>
                @endif

                <div class="mt-6 border-t border-slate-100 pt-6">
                    <p class="text-sm text-slate-500">Foto digital em alta resolução, sem marca d'água</p>
                    <p class="mt-1 text-3xl font-bold text-slate-950">R$ {{ number_format((float) $event->price_per_photo, 2, ',', '.') }}</p>
                    @if ($next_discount)
                        <p class="mt-2 text-sm font-medium text-emerald-700">Leve mais {{ $next_discount['missing_photos'] }} {{ $next_discount['missing_photos'] === 1 ? 'foto' : 'fotos' }} e ganhe {{ number_format($next_discount['percent'] * 100, 0) }}% de desconto.</p>
                    @endif
                </div>

                <div class="mt-6 space-y-3">
                    @if ($is_in_cart)
                        <button wire:click="remove_from_cart" class="fotx-button-secondary w-full py-3.5 text-red-600 hover:text-red-700">Remover do carrinho</button>
                    @else
                        <button wire:click="add_to_cart" class="fotx-button-primary w-full py-3.5">Adicionar ao carrinho</button>
                    @endif

                    @if ($cart_count > 0)
                        <a href="{{ route('checkout.show') }}" class="block w-full rounded-full bg-emerald-500 px-5 py-3.5 text-center text-sm font-bold text-slate-950 transition hover:bg-emerald-400">Finalizar compra ({{ $cart_count }})</a>
                    @endif

                    @if (filled(config('fotx.whatsapp_number')))
                        <a href="{{ route('tracking.events.whatsapp', [$event->slug, 'photo' => $event_photo->public_id]) }}" target="_blank" rel="noopener" class="block w-full text-center text-sm font-semibold text-slate-600 hover:text-slate-950">
                            Compartilhar no WhatsApp
                        </a>
                    @endif
                </div>

                @if (session('status'))
                    <p class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</p>
                @endif
            </aside>
        </div>
    </main>
</div>
