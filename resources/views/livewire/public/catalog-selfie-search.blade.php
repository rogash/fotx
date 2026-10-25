<div class="{{ $cart_count > 0 ? 'pb-28' : '' }}">
    <x-public.header />

    <main class="mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-6 sm:pt-12 lg:px-8">
        <section class="max-w-3xl">
            <p class="text-sm font-semibold text-emerald-700">Busca em todos os eventos</p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">Encontre suas fotos com uma selfie</h1>
            <p class="mt-4 text-lg leading-8 text-slate-600">
                Não precisa escolher o evento antes: comparamos sua selfie com os eventos da vitrine do Fotx e mostramos onde você aparece.
            </p>
        </section>

        <div class="fotx-card mt-8 overflow-hidden p-0">
            <form wire:submit="search" class="grid gap-6 p-5 sm:p-6 md:grid-cols-[auto_1fr] md:items-center">
                <x-public.selfie-picker :selfie="$selfie" />

                <div>
                    <h2 class="text-xl font-bold text-slate-950">Envie uma selfie</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Use uma foto de frente, com boa luz e sem óculos escuros. Ela é apagada automaticamente em até 48 horas.</p>
                    @error('selfie') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                    <label class="mt-4 flex gap-3 text-sm text-slate-600">
                        <input type="checkbox" wire:model="consent_accepted" class="mt-0.5 rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-600" />
                        <span>Autorizo o uso temporário da minha selfie para comparar meu rosto com as fotos de todos os eventos da vitrine do Fotx e localizar fotos em que eu apareça. A selfie é apagada automaticamente em até 48 horas.</span>
                    </label>
                    @error('consent_accepted') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                    <button class="fotx-button-primary mt-5 w-full px-6 py-3.5 sm:w-auto" wire:loading.attr="disabled" wire:target="search,selfie">
                        <span wire:loading.remove wire:target="search">Buscar em todos os eventos</span>
                        <span wire:loading wire:target="search">Procurando nos eventos...</span>
                    </button>
                </div>
            </form>
        </div>

        <p class="mt-4 text-sm leading-6 text-slate-500">
            Eventos compartilhados só por link ou QR Code não entram nesta busca. Nesse caso, abra o link que você recebeu do fotógrafo.
            <a href="{{ route('public.events.index') }}" class="font-semibold text-slate-700 underline underline-offset-2 hover:text-slate-950">Ver eventos da vitrine</a>
        </p>

        <x-public.cart-error class="sticky top-20 z-20" />

        @if ($results_by_event)
            <section x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="mt-10 scroll-mt-20 space-y-12">
                <div>
                    <h2 class="text-2xl font-bold text-slate-950">
                        Você aparece em {{ count($results_by_event) }} {{ count($results_by_event) === 1 ? 'evento' : 'eventos' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">Confira antes de comprar: pessoas parecidas podem aparecer. Cada compra é de um evento por vez.</p>
                </div>

                @foreach ($results_by_event as $group)
                    <div wire:key="evento-{{ $group['event']->id }}">
                        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h3 class="text-xl font-bold text-slate-950">{{ $group['event']->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ collect([$group['event']->event_date?->translatedFormat('j \d\e F \d\e Y'), $group['event']->location, count($group['photos']).' '.(count($group['photos']) === 1 ? 'foto' : 'fotos')])->filter()->implode(' · ') }}
                                </p>
                            </div>
                            <a href="{{ route('public.events.show', $group['event']->slug) }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Abrir evento</a>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                            @foreach ($group['photos'] as $result)
                                <x-public.photo-card
                                    :event="$group['event']"
                                    :photo="$result['photo']"
                                    :in-cart="in_array($result['photo']->id, $cart_photo_ids, true)"
                                    :score="$result['score']"
                                />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        @elseif ($has_searched)
            <div class="fotx-card mt-8 p-8 text-center">
                <h2 class="text-xl font-bold text-slate-950">Nenhuma foto encontrada</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                    @if ($searched_events_count === 0)
                        Ainda não há eventos com fotos na vitrine. Se você recebeu um link do fotógrafo, abra a galeria por ele.
                    @else
                        Procuramos em {{ $searched_events_count }} {{ $searched_events_count === 1 ? 'evento' : 'eventos' }} e não encontramos você. Tente outra selfie, de frente e com boa luz, ou abra o link do evento que recebeu.
                    @endif
                </p>
            </div>
        @endif
    </main>

    <x-public.cart-bar :count="$cart_count" :total="$cart_total" :discount-percent="$discount_percent" :next-discount="$next_discount" />
</div>
