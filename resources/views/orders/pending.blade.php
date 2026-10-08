@component('layouts.public', ['title' => 'Pedido criado'])
    <x-public.header />

    <main class="mx-auto max-w-xl px-4 py-10 sm:py-16">
        <div class="fotx-card p-6 text-center sm:p-8">
            @if ($order->status === 'paid')
                <p class="text-sm font-semibold text-emerald-700">Pagamento aprovado</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-950">Suas fotos estão liberadas</h1>
                <a href="{{ route('orders.downloads', [$order, $download_token]) }}" class="fotx-button-primary mt-6 w-full py-3.5">Baixar minhas fotos</a>
            @else
                <p class="text-sm font-semibold text-emerald-700">Pedido {{ $order->public_id }}</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-950">Falta só o pagamento</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    {{ $order->items->count() }} {{ $order->items->count() === 1 ? 'foto' : 'fotos' }} · Total de
                    <strong class="text-slate-950">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</strong>.
                    Assim que o pagamento for aprovado, os downloads são liberados.
                </p>

                @if ($order->payment_provider === 'mock')
                    <form method="POST" action="{{ route('payments.mock.approve', [$order, $download_token]) }}" class="mt-6">
                        @csrf
                        <button class="w-full rounded-full bg-emerald-500 px-5 py-3.5 text-sm font-bold text-slate-950">Simular aprovação</button>
                    </form>
                @elseif ($order->payment_checkout_url)
                    <a href="{{ $order->payment_checkout_url }}" class="mt-6 block w-full rounded-full bg-emerald-500 px-5 py-3.5 text-sm font-bold text-slate-950 transition hover:bg-emerald-400">Pagar com Mercado Pago</a>
                @endif

                @if (request()->filled('payment_id'))
                    {{-- Retorno do Mercado Pago: o webhook pode levar alguns segundos para confirmar. --}}
                    <p class="mt-4 text-sm font-medium text-slate-600">Confirmando seu pagamento. Esta página atualiza sozinha.</p>
                    <script>setTimeout(() => window.location.reload(), 10000);</script>
                @endif
            @endif

            @include('orders.partials.save-link')
        </div>
    </main>
@endcomponent
