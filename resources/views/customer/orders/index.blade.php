<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Minhas compras</h1>
            <p class="mt-1 text-sm text-slate-500">Acesse os downloads liberados dos pedidos pagos.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-4">
                @forelse ($orders as $order)
                    <a href="{{ route('orders.downloads', [$order, $order->download_token]) }}" class="block rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="text-lg font-semibold text-slate-950">{{ $order->event->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">Pedido {{ $order->public_id }} - {{ $order->items->count() }} foto(s)</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-slate-900">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</p>
                                <p class="mt-1 text-sm font-semibold text-emerald-700">Downloads liberados</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl bg-white p-8 text-sm text-slate-500 shadow-sm ring-1 ring-slate-200">Você ainda não comprou fotos.</div>
                @endforelse
            </div>

            <div class="mt-8">{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
