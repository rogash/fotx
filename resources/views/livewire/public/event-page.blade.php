<div>
    <x-public.header :event="$event" />

    <main>
        <section class="mx-auto max-w-6xl px-4 pt-6 sm:px-6 sm:pt-10 lg:px-8">
            <div class="grid items-center gap-6 lg:grid-cols-[1.1fr_0.9fr] lg:gap-12">
                <div class="order-2 lg:order-1">
                    <p class="text-sm font-semibold text-emerald-700">
                        {{ collect([$event->event_date?->translatedFormat('j \d\e F \d\e Y'), $event->location])->filter()->implode(' · ') ?: 'Galeria do evento' }}
                    </p>
                    <h1 class="mt-3 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">{{ $event->name }}</h1>
                    <p class="mt-4 max-w-xl text-lg leading-8 text-slate-600">
                        Encontre suas fotos em segundos: envie uma selfie ou busque pelo seu número e baixe em alta resolução.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-600">
                        <span><strong class="text-lg font-bold text-slate-950">{{ number_format($photos_count, 0, ',', '.') }}</strong> fotos</span>
                        <span><strong class="text-lg font-bold text-slate-950">R$ {{ number_format((float) $event->price_per_photo, 2, ',', '.') }}</strong> por foto</span>
                    </div>

                    @if ($discount_tiers)
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($discount_tiers as $minimum_quantity => $tier_discount_percent)
                                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-100">
                                    {{ $minimum_quantity }}+ fotos: {{ number_format($tier_discount_percent * 100, 0) }}% off
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-7 flex flex-wrap items-center gap-3">
                        <a href="#buscar" class="fotx-button-primary px-6 py-3.5">Encontrar minhas fotos</a>
                        @if (filled(config('fotx.whatsapp_number')))
                            <a href="{{ route('tracking.events.whatsapp', $event->slug) }}" target="_blank" rel="noopener" class="fotx-button-secondary px-6 py-3.5">
                                Falar no WhatsApp
                            </a>
                        @endif
                    </div>
                </div>

                <div class="order-1 lg:order-2">
                    <div class="aspect-[4/3] overflow-hidden rounded-3xl bg-slate-200 shadow-xl shadow-slate-900/10 ring-1 ring-slate-900/5">
                        @if ($event->cover_photo?->watermarked_path)
                            <img src="{{ route('media.photos.watermarked', $event->cover_photo) }}" class="h-full w-full object-cover" alt="Capa do evento {{ $event->name }}">
                        @else
                            <div class="flex h-full items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900">
                                <x-brand.logo variant="light" class="h-12 w-auto opacity-80" />
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section id="buscar" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            @if ($event->description)
                <p class="mb-6 max-w-3xl text-sm leading-6 text-slate-500">{{ $event->description }}</p>
            @endif
            <livewire:public.selfie-search :event="$event" />
        </section>

        <footer class="border-t border-slate-200/70 py-8">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                <p>Prévias com marca d'água · Pagamento seguro · Originais em alta resolução após a aprovação</p>
                <p>Galeria por <a href="https://fotx.com.br" class="font-semibold text-slate-700 hover:text-slate-950">Fotx</a></p>
            </div>
        </footer>
    </main>
</div>
