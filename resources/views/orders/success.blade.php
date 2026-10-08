@component('layouts.public', ['title' => 'Pagamento aprovado'])
    <x-public.header />

    <main class="mx-auto max-w-xl px-4 py-10 sm:py-16">
        <div class="fotx-card p-6 text-center sm:p-8">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-slate-950">Pagamento aprovado</h1>
            <p class="mt-2 text-sm text-slate-600">Pedido {{ $order->public_id }}. Suas fotos já estão liberadas em alta resolução.</p>
            <a href="{{ route('orders.downloads', [$order, $download_token]) }}" class="fotx-button-primary mt-6 w-full py-3.5">Baixar minhas fotos</a>

            @include('orders.partials.save-link')
        </div>
    </main>
@endcomponent
