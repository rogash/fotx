<div class="space-y-8">
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="fotx-card p-5">
            <p class="text-sm font-semibold text-slate-950">Tenho número, nome ou equipe</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Ideal para corridas, formaturas e eventos com identificação. Use o que recebeu no evento.</p>
        </div>
        <div class="fotx-card p-5">
            <p class="text-sm font-semibold text-slate-950">Quero buscar por selfie</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Envie uma foto clara do rosto. A selfie é usada temporariamente para localizar resultados prováveis.</p>
        </div>
    </div>

    <form wire:submit="search_by_text" class="fotx-card p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="flex-1">
                <label class="text-sm font-semibold text-slate-800">Buscar por número, nome ou equipe</label>
                <input
                    type="search"
                    wire:model="participant_query"
                    class="fotx-input mt-3 w-full px-4 py-4 text-sm"
                    placeholder="Ex: 3087, Ana Silva, Equipe Fotx"
                />
                @error('participant_query') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button class="fotx-button-primary px-6 py-4 font-bold">
                <span wire:loading.remove wire:target="search_by_text">Buscar fotos</span>
                <span wire:loading wire:target="search_by_text">Buscando...</span>
            </button>
        </div>
        <p class="mt-3 text-sm text-slate-500">Use o número de peito, nome, turma, equipe ou tag informada pelo fotógrafo.</p>
    </form>

    <form wire:submit="search" class="fotx-card p-6">
        <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="rounded-[1.5rem] bg-slate-50 p-4 ring-1 ring-slate-200">
                <div class="aspect-square overflow-hidden rounded-[1.25rem] bg-white">
                    @if ($selfie)
                        <img src="{{ $selfie->temporaryUrl() }}" class="h-full w-full object-contain" alt="Preview da selfie">
                    @else
                        <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-950 text-xl font-black text-white">F</div>
                            <p class="mt-4 text-sm font-semibold text-slate-800">Preview da selfie</p>
                            <p class="mt-1 text-sm text-slate-500">Escolha uma imagem com rosto visível e boa luz.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col justify-between gap-5">
                <div>
                    <label class="text-sm font-semibold text-slate-800">Enviar selfie</label>
                    <p class="mt-1 text-sm text-slate-500">JPG, PNG ou WebP até 10MB. O resultado pode incluir fotos parecidas para você conferir.</p>
                    <input type="file" wire:model="selfie" accept=".jpg,.jpeg,.png,.webp" class="mt-3 w-full rounded-2xl border border-dashed border-slate-300 bg-white/80 p-4 text-sm text-slate-600" />
                    @error('selfie') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    <label class="mt-4 flex gap-3 text-sm text-slate-600">
                        <input type="checkbox" wire:model="consent_accepted" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600" />
                        <span>Autorizo o uso temporário da minha selfie para localizar fotos em que eu possa aparecer neste evento.</span>
                    </label>
                    @error('consent_accepted') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button class="fotx-button-primary px-6 py-4 font-bold">
                        <span wire:loading.remove wire:target="search">Enviar selfie</span>
                        <span wire:loading wire:target="search">Buscando...</span>
                    </button>
                    @if ($cart_count > 0)
                        <a href="{{ route('cart.show') }}" class="fotx-button-secondary px-5 py-4">Finalizar compra ({{ $cart_count }})</a>
                    @endif
                </div>
            </div>
        </div>

        <div wire:loading wire:target="selfie" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm font-medium text-slate-600">Carregando preview...</div>
        <div wire:loading wire:target="search" class="mt-4 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">Buscando fotos prováveis neste evento...</div>
    </form>

    @if (session('status'))
        <p class="rounded-[1.5rem] bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-100">{{ session('status') }}</p>
    @endif

    @if ($results)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-xl font-bold text-slate-950">Fotos encontradas</h3>
                <p class="mt-1 text-sm text-slate-500">
                    {{ count($results) }} resultado(s) encontrados
                    {{ $result_source === 'text' ? 'pela busca do evento.' : 'por similaridade.' }}
                </p>
            </div>
            <a href="{{ route('cart.show') }}" class="fotx-button-primary">Finalizar compra</a>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($results as $result)
                @php $is_in_cart = in_array($result['photo']->id, $cart_photo_ids, true); @endphp
                <div class="fotx-card p-3 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/5">
                    <a href="{{ route('public.photos.show', [$event->slug, $result['photo']]) }}">
                        <span class="flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                            <img src="{{ route('media.photos.watermarked', $result['photo']) }}" class="h-full w-full object-contain" alt="Foto encontrada">
                        </span>
                    </a>
                    <p class="mt-3 truncate px-1 text-sm font-semibold text-slate-900">{{ $result['photo']->filename }}</p>
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <a href="{{ route('public.photos.show', [$event->slug, $result['photo']]) }}" class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">{{ number_format($result['score'] * 100, 0) }}% compatível</a>
                        @if ($is_in_cart)
                            <button wire:click="remove_from_cart('{{ $result['photo']->public_id }}')" class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-600">Remover</button>
                        @else
                            <button wire:click="add_to_cart('{{ $result['photo']->public_id }}')" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Adicionar</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($has_searched)
        <div class="fotx-card p-8 text-center">
            <h3 class="text-xl font-bold text-slate-950">Nenhuma foto encontrada</h3>
            <p class="mt-2 text-sm text-slate-500">Tente outra selfie, busque por número/nome ou confirme se o fotógrafo já publicou as fotos deste evento.</p>
        </div>
    @endif
</div>
