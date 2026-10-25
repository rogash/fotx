<div>
    <x-public.header />

    <main class="mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-6 sm:pt-12 lg:px-8">
        <section class="max-w-3xl">
            <p class="text-sm font-semibold text-emerald-700">Eventos no Fotx</p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">Encontre o seu evento</h1>
            <p class="mt-4 text-lg leading-8 text-slate-600">
                Busque pelo nome do evento ou pela cidade, abra a galeria e encontre suas fotos com uma selfie.
            </p>

            <label for="busca-eventos" class="sr-only">Buscar evento</label>
            <input
                id="busca-eventos"
                type="search"
                wire:model.live.debounce.400ms="search"
                maxlength="100"
                placeholder="Ex.: Corrida SP, formatura, Curitiba"
                class="fotx-input mt-6 w-full"
                autocomplete="off"
            />

            <a href="{{ route('public.catalog.search') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                Não sabe o evento? Busque com uma selfie em todos os eventos
                <span aria-hidden="true">→</span>
            </a>
        </section>

        <section class="mt-10" aria-live="polite">
            @if ($events->isEmpty())
                <div class="rounded-2xl bg-white p-8 text-center ring-1 ring-slate-200">
                    <p class="text-base font-semibold text-slate-900">
                        {{ $term !== '' ? 'Nenhum evento encontrado para "'.$term.'".' : 'Ainda não há eventos na vitrine.' }}
                    </p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Nem todo evento aparece aqui: o fotógrafo pode ter compartilhado a galeria só por link ou QR Code. Nesse caso, use o link que você recebeu.
                    </p>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($events as $event)
                        <a
                            href="{{ route('public.events.show', $event->slug) }}"
                            wire:key="event-{{ $event->id }}"
                            class="group overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/5"
                        >
                            <div class="aspect-[4/3] overflow-hidden bg-slate-200">
                                @if ($event->cover_photo?->watermarked_path)
                                    <img src="{{ route('media.photos.watermarked', $event->cover_photo) }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" alt="Capa do evento {{ $event->name }}" loading="lazy">
                                @else
                                    <div class="flex h-full items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900">
                                        <x-brand.logo variant="light" class="opacity-80" />
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <h2 class="text-lg font-bold leading-snug text-slate-950">{{ $event->name }}</h2>
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ collect([$event->event_date?->translatedFormat('j \d\e F \d\e Y'), $event->location])->filter()->implode(' · ') }}
                                </p>
                                @if ($event->photographer)
                                    <p class="mt-3 text-xs font-semibold text-slate-500">Fotos por {{ $event->photographer->name }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $events->links() }}
                </div>
            @endif
        </section>
    </main>
</div>
