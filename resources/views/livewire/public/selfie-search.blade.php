<div class="space-y-8">
    <form wire:submit="search" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
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
            <button class="rounded-2xl bg-slate-950 px-6 py-4 text-sm font-bold text-white shadow-sm hover:bg-slate-800">Enviar selfie</button>
        </div>
        <p wire:loading wire:target="search,selfie" class="mt-3 text-sm text-slate-500">Buscando fotos prováveis...</p>
    </form>

    @if (session('status')) <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</p> @endif

    @if ($results)
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-950">Fotos prováveis</h3>
            <a href="{{ route('cart.show') }}" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Finalizar compra</a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($results as $result)
                <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                    <img src="{{ route('media.photos.watermarked', $result['photo']) }}" class="aspect-[4/3] w-full rounded-xl object-cover" alt="Foto encontrada">
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">{{ number_format($result['score'] * 100, 0) }}% match</span>
                        <button wire:click="add_to_cart({{ $result['photo']->id }})" class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Adicionar ao carrinho</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
