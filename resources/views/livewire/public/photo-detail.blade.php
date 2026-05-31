<main class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="flex items-center justify-between">
            <a href="{{ route('public.events.show', $event->slug) }}" class="inline-flex items-center">
                <x-brand.logo class="h-10 w-auto" />
            </a>
            <livewire:public.cart-badge />
        </nav>

        <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_380px]">
            <section class="overflow-hidden rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                <div class="flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                    <img src="{{ route('media.photos.watermarked', $event_photo) }}" class="h-full w-full object-contain" alt="{{ $event_photo->filename }}">
                </div>
            </section>

            <aside class="self-start rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">{{ $event->name }}</p>
                <h1 class="mt-3 text-2xl font-bold text-slate-950">Foto do evento</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $event_photo->filename }}</p>

                @if ($event_photo->participant_code || $event_photo->search_keywords)
                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        @if ($event_photo->participant_code)
                            <p><span class="font-semibold text-slate-800">Número:</span> {{ $event_photo->participant_code }}</p>
                        @endif
                        @if ($event_photo->search_keywords)
                            <p class="mt-1"><span class="font-semibold text-slate-800">Tags:</span> {{ $event_photo->search_keywords }}</p>
                        @endif
                    </div>
                @endif

                <div class="mt-6 rounded-2xl bg-emerald-50 p-4">
                    <p class="text-sm font-medium text-emerald-900">Foto digital em alta resolução</p>
                    <p class="mt-1 text-3xl font-black text-emerald-950">R$ {{ number_format((float) $event->price_per_photo, 2, ',', '.') }}</p>
                </div>

                @if (session('status'))
                    <p class="mt-4 rounded-2xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</p>
                @endif

                <div class="mt-6 space-y-3">
                    @if ($is_in_cart)
                        <button wire:click="remove_from_cart" class="w-full rounded-2xl border border-red-200 px-5 py-4 text-sm font-bold text-red-600">Remover do carrinho</button>
                    @else
                        <button wire:click="add_to_cart" class="w-full rounded-2xl bg-slate-950 px-5 py-4 text-sm font-bold text-white">Adicionar ao carrinho</button>
                    @endif

                    <a href="{{ route('checkout.show') }}" class="block w-full rounded-2xl bg-emerald-600 px-5 py-4 text-center text-sm font-bold text-white">Finalizar compra{{ $cart_count > 0 ? ' ('.$cart_count.')' : '' }}</a>
                    @if (filled(config('fotx.whatsapp_number')))
                        <a
                            href="{{ route('tracking.events.whatsapp', [$event->slug, 'photo' => $event_photo->public_id]) }}"
                            target="_blank"
                            rel="noopener"
                            class="block w-full rounded-2xl border border-emerald-200 px-5 py-4 text-center text-sm font-bold text-emerald-700"
                        >
                            Compartilhar no WhatsApp
                        </a>
                    @endif
                    <a href="{{ route('public.events.show', $event->slug) }}" class="block w-full rounded-2xl border border-slate-300 px-5 py-4 text-center text-sm font-semibold text-slate-700">Voltar ao evento</a>
                </div>
            </aside>
        </div>
    </div>
</main>
