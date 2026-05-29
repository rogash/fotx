<x-guest-layout>
    <div class="text-center">
        <h1 class="text-2xl font-bold text-slate-950">Pagamento aprovado</h1>
        <p class="mt-3 text-sm text-slate-600">Seu pedido {{ $order->public_id }} foi aprovado e as fotos já estão liberadas.</p>
        <a href="{{ route('orders.downloads', [$order, $download_token]) }}" class="mt-6 inline-flex rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Ver downloads</a>
    </div>
</x-guest-layout>
