<main class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <a href="/" class="text-xl font-extrabold text-slate-950">Fotx</a>
            <a href="{{ route('checkout.show') }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Finalizar compra</a>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h1 class="text-2xl font-bold text-slate-950">Carrinho</h1>
            <div class="mt-6 divide-y divide-slate-100">
                @forelse ($items as $item)
                    <div class="flex items-center justify-between gap-4 py-4">
                        <div class="flex items-center gap-4">
                            <img src="{{ route('media.photos.thumbnail', $item['photo']) }}" class="h-20 w-28 rounded-xl object-cover" alt="{{ $item['photo']->filename }}">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item['photo']->event->name }}</p>
                                <p class="text-sm text-slate-500">{{ $item['photo']->filename }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">R$ {{ number_format($item['price'], 2, ',', '.') }}</p>
                            <button wire:click="remove_photo({{ $item['event_photo_id'] }})" class="mt-2 text-sm font-semibold text-red-600">Remover</button>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-slate-500">Seu carrinho está vazio.</p>
                @endforelse
            </div>
            <div class="mt-6 flex justify-end text-xl font-bold text-slate-950">Total: R$ {{ number_format($total, 2, ',', '.') }}</div>
        </div>
    </div>
</main>
