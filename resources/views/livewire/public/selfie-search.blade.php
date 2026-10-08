<div class="{{ $cart_count > 0 ? 'pb-28' : '' }}">
    <div class="fotx-card overflow-hidden p-0">
        <div class="grid grid-cols-2 border-b border-slate-200/80 p-1.5" role="tablist">
            @foreach (['selfie' => 'Buscar por selfie', 'text' => 'Número ou nome'] as $mode => $label)
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $search_mode === $mode ? 'true' : 'false' }}"
                    wire:click="set_search_mode('{{ $mode }}')"
                    class="rounded-2xl px-4 py-3 text-sm font-semibold transition {{ $search_mode === $mode ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-600 hover:text-slate-950' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($search_mode === 'selfie')
            <form wire:submit="search" class="grid gap-6 p-5 sm:p-6 md:grid-cols-[auto_1fr] md:items-center">
                <label class="relative mx-auto flex h-40 w-40 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-slate-300 bg-slate-50 text-center transition hover:border-emerald-500 hover:bg-emerald-50/50">
                    @if ($selfie)
                        <img src="{{ $selfie->temporaryUrl() }}" class="absolute inset-0 h-full w-full object-cover" alt="Sua selfie">
                    @else
                        <svg class="h-9 w-9 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A2.5 2.5 0 0 1 6.5 6h1.2l1.1-1.6A1 1 0 0 1 9.6 4h4.8a1 1 0 0 1 .8.4L16.3 6h1.2A2.5 2.5 0 0 1 20 8.5v8a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-8Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
                        <span class="mt-2 px-4 text-sm font-semibold text-slate-700">Tirar ou escolher selfie</span>
                    @endif
                    <input type="file" wire:model="selfie" accept="image/jpeg,image/png,image/webp" capture="user" class="sr-only" />
                    <span wire:loading wire:target="selfie" class="absolute inset-0 flex items-center justify-center bg-white/80 text-sm font-semibold text-slate-600">Carregando...</span>
                </label>

                <div>
                    <h2 class="text-xl font-bold text-slate-950">Encontre suas fotos pelo rosto</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Use uma selfie de frente, com boa luz e sem óculos escuros. Ela é usada só para esta busca e apagada em até 24 horas.</p>
                    @error('selfie') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                    <label class="mt-4 flex gap-3 text-sm text-slate-600">
                        <input type="checkbox" wire:model="consent_accepted" class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600" />
                        <span>Autorizo o uso temporário da minha selfie para localizar fotos em que eu apareça neste evento.</span>
                    </label>
                    @error('consent_accepted') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                    <button class="fotx-button-primary mt-5 w-full px-6 py-3.5 sm:w-auto" wire:loading.attr="disabled" wire:target="search,selfie">
                        <span wire:loading.remove wire:target="search">Buscar minhas fotos</span>
                        <span wire:loading wire:target="search">Procurando suas fotos...</span>
                    </button>
                </div>
            </form>
        @else
            <form wire:submit="search_by_text" class="p-5 sm:p-6">
                <h2 class="text-xl font-bold text-slate-950">Busque pelo número ou nome</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Número de peito, nome, turma ou equipe, como o fotógrafo cadastrou.</p>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <input type="search" wire:model="participant_query" class="fotx-input w-full px-4 py-3.5 text-base" placeholder="Ex.: 3087 ou Ana Silva" />
                    <button class="fotx-button-primary shrink-0 px-6 py-3.5">
                        <span wire:loading.remove wire:target="search_by_text">Buscar fotos</span>
                        <span wire:loading wire:target="search_by_text">Buscando...</span>
                    </button>
                </div>
                @error('participant_query') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
            </form>
        @endif
    </div>

    @if (session('status'))
        <p class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-100">{{ session('status') }}</p>
    @endif

    @if ($results)
        <section x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="mt-10 scroll-mt-20">
            <div class="mb-4">
                <h2 class="text-2xl font-bold text-slate-950">{{ count($results) }} {{ count($results) === 1 ? 'foto encontrada' : 'fotos encontradas' }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $result_source === 'selfie' ? 'Confira antes de comprar: pessoas parecidas podem aparecer.' : 'Resultado da busca por número ou nome.' }}
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($results as $result)
                    <x-public.photo-card
                        :event="$event"
                        :photo="$result['photo']"
                        :in-cart="in_array($result['photo']->id, $cart_photo_ids, true)"
                        :score="$result['source'] === 'selfie' ? $result['score'] : null"
                    />
                @endforeach
            </div>
        </section>
    @elseif ($has_searched)
        <div class="fotx-card mt-8 p-8 text-center">
            <h2 class="text-xl font-bold text-slate-950">Nenhuma foto encontrada</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                {{ $result_source === 'selfie' ? 'Tente outra selfie, de frente e com boa luz, ou busque pelo seu número.' : 'Confira o número ou nome digitado, ou tente buscar por selfie.' }}
            </p>
        </div>
    @endif

    @if ($gallery_photos)
        <section class="mt-14">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-950">Todas as fotos</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ number_format($gallery_photos->total(), 0, ',', '.') }} fotos neste evento</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($gallery_photos as $photo)
                    <x-public.photo-card :event="$event" :photo="$photo" :in-cart="in_array($photo->id, $cart_photo_ids, true)" />
                @endforeach
            </div>
            <div class="mt-6">{{ $gallery_photos->links() }}</div>
        </section>
    @endif

    @if ($cart_count > 0)
        <div class="fixed inset-x-0 bottom-0 z-40 p-3 sm:p-4" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 rounded-2xl bg-slate-950 px-4 py-3 text-white shadow-2xl shadow-slate-950/30 sm:px-5">
                <div class="min-w-0">
                    <p class="text-sm font-semibold">
                        {{ $cart_count }} {{ $cart_count === 1 ? 'foto' : 'fotos' }} · R$ {{ number_format($cart_total, 2, ',', '.') }}
                    </p>
                    <p class="truncate text-xs text-emerald-300">
                        @if ($next_discount)
                            Mais {{ $next_discount['missing_photos'] }} {{ $next_discount['missing_photos'] === 1 ? 'foto' : 'fotos' }} e você ganha {{ number_format($next_discount['percent'] * 100, 0) }}% de desconto
                        @elseif ($discount_percent > 0)
                            Desconto de {{ number_format($discount_percent * 100, 0) }}% aplicado
                        @endif
                    </p>
                </div>
                <a href="{{ route('checkout.show') }}" class="shrink-0 rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-emerald-300">Finalizar compra</a>
            </div>
        </div>
    @endif
</div>
