<x-guest-layout>
    <div class="text-center">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Pedido #{{ $order->id }}</p>
        <h1 class="mt-3 text-2xl font-bold text-slate-950">Pagamento pendente</h1>
        <p class="mt-3 text-sm text-slate-600">Seu pedido foi criado. Assim que o pagamento for aprovado, os downloads serão liberados.</p>

        @if ($order->payment_provider === 'mock')
            <form method="POST" action="{{ route('payments.mock.approve', [$order, $download_token]) }}" class="mt-6">
                @csrf
                <button class="inline-flex rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Simular aprovação</button>
            </form>
        @elseif ($order->payment_checkout_url)
            <a href="{{ $order->payment_checkout_url }}" class="mt-6 inline-flex rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Pagar com Mercado Pago</a>
        @endif

        <p class="mt-4 text-xs text-slate-500">Os downloads só ficam disponíveis depois da confirmação do pagamento.</p>
    </div>
</x-guest-layout>
