<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ $event->name }}</h1>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $event->status }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $event->location ?: 'Local nao informado' }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('events.photos', $event) }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Fotos</a>
                <a href="{{ route('events.orders', $event) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Pedidos</a>
                <a href="{{ route('events.edit', $event) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Editar</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Link publico do evento</p>
                        <p class="mt-2 break-all text-sm text-slate-700">{{ $event->public_url() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            x-on:click="navigator.clipboard.writeText('{{ $event->public_url() }}'); copied = true; setTimeout(() => copied = false, 1800)"
                            class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white"
                        >
                            <span x-show="! copied">Copiar link publico</span>
                            <span x-show="copied">Link copiado</span>
                        </button>
                        <a href="{{ route('public.events.show', $event->slug) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Abrir pagina</a>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Fotos', 'value' => $event->photos_count],
                    ['label' => 'Prontas', 'value' => $event->ready_photos_count],
                    ['label' => 'Pedidos', 'value' => $event->orders_count],
                    ['label' => 'Vendas pagas', 'value' => $paid_orders_count],
                    ['label' => 'Faturamento', 'value' => 'R$ '.number_format((float) $event_revenue, 2, ',', '.')],
                ] as $card)
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-950">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </section>

            <section class="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-950">Publicacao</h2>
                    <p class="mt-2 text-sm text-slate-500">Controle quando o evento aparece para clientes.</p>
                    <form method="POST" action="{{ route('events.publish', $event) }}" class="mt-6">
                        @csrf
                        <button class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">{{ $event->status === 'published' ? 'Despublicar evento' : 'Publicar evento' }}</button>
                    </form>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-slate-950">Pedidos recentes</h2>
                        <a href="{{ route('events.orders', $event) }}" class="text-sm font-semibold text-slate-700">Ver todos</a>
                    </div>
                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse ($recent_orders as $order)
                            <div class="flex items-center justify-between gap-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $order->buyer_email }}</p>
                                    <p class="text-sm text-slate-500">{{ $order->items_count }} foto(s) - {{ $order->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-slate-900">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</p>
                                    <p class="text-sm text-slate-500">{{ $order->status }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-slate-500">Nenhum pedido ainda.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
