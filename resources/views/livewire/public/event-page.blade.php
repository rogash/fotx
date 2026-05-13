<main class="min-h-screen bg-slate-950">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.28),_transparent_36%),linear-gradient(135deg,_#020617_0%,_#0f172a_48%,_#14532d_100%)]"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <nav class="flex items-center justify-between text-white">
                <a href="{{ route('public.events.show', $event->slug) }}" class="text-xl font-extrabold tracking-tight">Fotx</a>
                <a href="{{ route('cart.show') }}" class="rounded-2xl bg-white/10 px-4 py-2 text-sm font-semibold ring-1 ring-white/20 hover:bg-white/15">Carrinho</a>
            </nav>

            <div class="grid min-h-[76vh] items-center gap-10 py-14 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="text-white">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-200">{{ $event->location ?: 'Evento Fotx' }}</p>
                    <h1 class="mt-5 max-w-3xl text-5xl font-extrabold leading-tight sm:text-6xl">Encontre suas fotos em segundos</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">Envie uma selfie e veja as fotos em que você aparece neste evento.</p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="#buscar" class="rounded-2xl bg-emerald-400 px-6 py-4 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-950/30 hover:bg-emerald-300">Encontrar minhas fotos</a>
                        <span class="text-sm font-semibold text-slate-200">R$ {{ number_format((float) $event->price_per_photo, 2, ',', '.') }} por foto</span>
                    </div>
                </div>
                <div class="rounded-2xl bg-white/10 p-4 shadow-2xl ring-1 ring-white/20 backdrop-blur">
                    <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-slate-800">
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

    <section id="buscar" class="bg-slate-50 py-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-slate-950">{{ $event->name }}</h2>
                @if ($event->description)
                    <p class="mt-3 max-w-3xl text-slate-600">{{ $event->description }}</p>
                @endif
            </div>
            <livewire:public.selfie-search :event="$event" />
        </div>
    </section>
</main>
