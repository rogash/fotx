<main class="min-h-screen bg-[#f5f5f7]">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[linear-gradient(180deg,_#020617_0%,_#0f172a_64%,_#f5f5f7_64%)]"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <nav class="flex items-center justify-between text-white">
                <a href="{{ route('public.events.show', $event->slug) }}" class="inline-flex items-center">
                    <x-brand.logo variant="light" class="h-10 w-auto" />
                </a>
                <livewire:public.cart-badge />
            </nav>

            <div class="grid min-h-[76vh] items-center gap-10 py-14 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="text-white">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-200">{{ $event->location ?: 'Evento Fotx' }}</p>
                    <h1 class="mt-5 max-w-3xl text-5xl font-semibold leading-[0.98] sm:text-7xl">Encontre suas fotos em segundos</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">Busque por selfie, número de peito, nome ou equipe. Escolha suas fotos, pague online e baixe os arquivos originais com segurança.</p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="#buscar" class="rounded-full bg-white px-6 py-4 text-sm font-bold text-slate-950 shadow-lg shadow-slate-950/20 transition hover:-translate-y-0.5">Encontrar minhas fotos</a>
                        @if (filled(config('fotx.whatsapp_number')))
                            <a
                                href="{{ route('tracking.events.whatsapp', $event->slug) }}"
                                target="_blank"
                                rel="noopener"
                                class="rounded-full border border-white/25 px-6 py-4 text-sm font-bold text-white transition hover:bg-white/10"
                            >
                                Falar no WhatsApp
                            </a>
                        @endif
                        <span class="text-sm font-semibold text-slate-200">R$ {{ number_format((float) $event->price_per_photo, 2, ',', '.') }} por foto</span>
                    </div>
                    <div class="mt-8 grid max-w-xl gap-3 sm:grid-cols-3">
                        <div class="rounded-[1.4rem] bg-white/10 p-4 ring-1 ring-white/15">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-100">1. Busque</p>
                            <p class="mt-2 text-sm text-slate-200">Selfie ou número do evento.</p>
                        </div>
                        <div class="rounded-[1.4rem] bg-white/10 p-4 ring-1 ring-white/15">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-100">2. Escolha</p>
                            <p class="mt-2 text-sm text-slate-200">Veja previews com marca d'água.</p>
                        </div>
                        <div class="rounded-[1.4rem] bg-white/10 p-4 ring-1 ring-white/15">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-100">3. Baixe</p>
                            <p class="mt-2 text-sm text-slate-200">Originais liberados após pagar.</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-[2rem] bg-white/10 p-3 shadow-2xl shadow-slate-950/25 ring-1 ring-white/20 backdrop-blur">
                    <div class="aspect-[4/3] overflow-hidden rounded-[1.6rem] bg-slate-800">
                        @if ($event->cover_photo?->watermarked_path)
                            <img src="{{ route('media.photos.watermarked', $event->cover_photo) }}" class="h-full w-full object-cover" alt="{{ $event->name }}">
                        @else
                            <div class="flex h-full items-center justify-center bg-gradient-to-br from-slate-800 to-emerald-900 text-3xl font-black text-white">FOTX</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="buscar" class="bg-[#f5f5f7] py-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Galeria do evento</p>
                    <h2 class="mt-2 text-3xl font-bold text-slate-950">{{ $event->name }}</h2>
                    @if ($event->description)
                        <p class="mt-3 max-w-3xl text-slate-600">{{ $event->description }}</p>
                    @endif
                </div>
                <div class="rounded-full bg-white/80 px-5 py-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200">
                    R$ {{ number_format((float) $event->price_per_photo, 2, ',', '.') }} por foto
                </div>
            </div>
            <livewire:public.selfie-search :event="$event" />
        </div>
    </section>
</main>
