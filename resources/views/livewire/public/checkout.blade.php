<main class="min-h-screen bg-slate-50">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_0.8fr] lg:px-8">
        <form wire:submit="start_payment" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h1 class="text-2xl font-bold text-slate-950">Checkout</h1>
            <div class="mt-6 space-y-4">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Nome</label>
                    <input wire:model="buyer_name" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" />
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Email</label>
                    <input type="email" wire:model="buyer_email" class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" />
                    @error('buyer_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button @disabled($items->isEmpty()) class="w-full rounded-2xl bg-emerald-600 px-5 py-4 text-sm font-bold text-white disabled:opacity-40">Ir para pagamento</button>
            </div>
        </form>
        <aside class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-bold text-slate-950">Resumo</h2>
            <div class="mt-4 space-y-3">
                @foreach ($items as $item)
                    <div class="flex justify-between text-sm"><span class="truncate">{{ $item['photo']->filename }}</span><span>R$ {{ number_format($item['price'], 2, ',', '.') }}</span></div>
                @endforeach
            </div>
            <div class="mt-6 border-t pt-4 text-xl font-bold">R$ {{ number_format($total, 2, ',', '.') }}</div>
        </aside>
    </div>
</main>
