<div class="space-y-8">
    <form wire:submit="search" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                <div class="aspect-square overflow-hidden rounded-2xl bg-white">
                    @if ($selfie)
                        <img src="{{ $selfie->temporaryUrl() }}" class="h-full w-full object-cover" alt="Preview da selfie">
                    @else
                        <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-950 text-xl font-black text-white">F</div>
                            <p class="mt-4 text-sm font-semibold text-slate-800">Preview da selfie</p>
                            <p class="mt-1 text-sm text-slate-500">A imagem aparece aqui antes da busca.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col justify-between gap-5">
                <div>
                    <label class="text-sm font-semibold text-slate-800">Enviar selfie</label>
                    <input type="file" wire:model="selfie" accept=".jpg,.jpeg,.png,.webp" class="mt-3 w-full rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-600" />
                    @error('selfie') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    <label class="mt-4 flex gap-3 text-sm text-slate-600">
                        <input type="checkbox" wire:model="consent_accepted" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600" />
                        <span>Autorizo o uso temporário da minha selfie para localizar fotos em que eu possa aparecer neste evento.</span>
                    </label>
                    @error('consent_accepted') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button class="rounded-2xl bg-slate-950 px-6 py-4 text-sm font-bold text-white shadow-sm hover:bg-slate-800">
                        <span wire:loading.remove wire:target="search">Enviar selfie</span>
                        <span wire:loading wire:target="search">Buscando...</span>
                    </button>
                    @if ($cart_count > 0)
                        <a href="{{ route('cart.show') }}" class="rounded-2xl border border-slate-300 px-5 py-4 text-sm font-semibold text-slate-700">Finalizar compra ({{ $cart_count }})</a>
                    @endif
                </div>
            </div>
        </div>

        <div wire:loading wire:target="selfie" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm font-medium text-slate-600">Carregando preview...</div>
        <div wire:loading wire:target="search" class="mt-4 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">Buscando fotos prováveis neste evento...</div>
    </form>

    @if (session('status'))
        <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</p>
    @endif

    @if ($results)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-xl font-bold text-slate-950">Fotos prováveis</h3>
                <p class="mt-1 text-sm text-slate-500">{{ count($results) }} resultado(s) encontrados por similaridade mock.</p>
            </div>
            <a href="{{ route('cart.show') }}" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Finalizar compra</a>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($results as $result)
                @php $is_in_cart = in_array($result['photo']->id, $cart_photo_ids, true); @endphp
                <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                    <img src="{{ route('media.photos.watermarked', $result['photo']) }}" class="aspect-[4/3] w-full rounded-xl object-cover" alt="Foto encontrada">
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">{{ number_format($result['score'] * 100, 0) }}% match</span>
                        @if ($is_in_cart)
                            <button wire:click="remove_from_cart({{ $result['photo']->id }})" class="rounded-2xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-600">Remover</button>
                        @else
                            <button wire:click="add_to_cart({{ $result['photo']->id }})" class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Adicionar</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($has_searched)
        <div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
            <h3 class="text-xl font-bold text-slate-950">Nenhuma foto provável encontrada</h3>
            <p class="mt-2 text-sm text-slate-500">Tente uma selfie mais iluminada ou confira se este evento já tem fotos processadas.</p>
        </div>
    @endif
</div>
