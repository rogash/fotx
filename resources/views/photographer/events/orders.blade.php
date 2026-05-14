<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Pedidos de {{ $event->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Acompanhe vendas, compradores e fotos adquiridas.</p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Voltar ao evento</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Pedidos pagos</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">{{ $paid_orders_count }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Faturamento</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-950">R$ {{ number_format((float) $event_revenue, 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pedido</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Comprador</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Fotos</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">#{{ $order->id }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <p class="font-medium text-slate-900">{{ $order->buyer_name ?: 'Sem nome' }}</p>
                                        <p>{{ $order->buyer_email }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ $order->items->count() }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700">{{ $order->status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">Nenhum pedido para este evento ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
