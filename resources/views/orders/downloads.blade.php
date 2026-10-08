@component('layouts.public', ['title' => 'Suas fotos'])
    <x-public.header />

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950">Suas fotos</h1>
        <p class="mt-2 text-sm text-slate-500">Pedido {{ $order->public_id }} · {{ $order->items->count() }} {{ $order->items->count() === 1 ? 'foto' : 'fotos' }} em alta resolução.</p>

        <ul class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach ($order->items as $item)
                <li class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                    <div class="aspect-[4/3] bg-slate-100">
                        <img src="{{ route('media.photos.thumbnail', $item->event_photo) }}" loading="lazy" class="h-full w-full object-contain" alt="Foto comprada">
                    </div>
                    <div class="p-2">
                        <a href="{{ $download_links[$item->event_photo_id] }}" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-500 px-3 py-2 text-sm font-bold text-slate-950 transition hover:bg-emerald-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 4v12m0 0-4-4m4 4 4-4"/></svg>
                            Baixar
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>

        <p class="mt-6 text-xs text-slate-500">Por segurança, cada botão de download vale por 15 minutos. Se expirar, é só recarregar esta página.</p>

        <div class="mx-auto max-w-xl">
            @include('orders.partials.save-link')
        </div>
    </main>
@endcomponent
