<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ $role === 'customer' ? 'Minhas fotos' : 'Dashboard Fotx' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $role === 'admin' ? 'Visao geral da plataforma' : ($role === 'photographer' ? 'Gestao dos seus eventos e vendas' : 'Encontre eventos, acompanhe compras e baixe suas fotos') }}
                </p>
            </div>
            @if ($role !== 'customer')
                <a href="{{ route('events.create') }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Novo evento</a>
            @else
                <a href="{{ route('cart.show') }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Ver carrinho</a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($role === 'customer')
                <div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
                    <section class="rounded-2xl bg-slate-950 p-8 text-white shadow-sm">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-300">Area do cliente</p>
                        <h2 class="mt-4 max-w-2xl text-4xl font-extrabold leading-tight">Encontre suas fotos usando uma selfie.</h2>
                        <p class="mt-4 max-w-xl text-slate-300">Acesse um evento publicado, envie sua selfie com consentimento e finalize a compra das fotos encontradas.</p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            @forelse ($featured_events as $event)
                                <a href="{{ route('public.events.show', $event->slug) }}" class="rounded-2xl bg-white px-5 py-3 text-sm font-bold text-slate-950">{{ $event->name }}</a>
                            @empty
                                <span class="rounded-2xl bg-white/10 px-5 py-3 text-sm font-semibold text-slate-200">Nenhum evento publicado ainda</span>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <p class="text-sm font-medium text-slate-500">Compras realizadas</p>
                        <p class="mt-3 text-4xl font-semibold text-slate-950">{{ $customer_orders_count }}</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('cart.show') }}" class="inline-flex rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Abrir carrinho</a>
                            <a href="{{ route('customer.orders.index') }}" class="inline-flex rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Minhas compras</a>
                        </div>
                    </section>
                </div>

                <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-950">Ultimas compras</h2>
                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse ($customer_orders as $order)
                            <a href="{{ route('orders.downloads', [$order, $order->download_token]) }}" class="flex items-center justify-between gap-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $order->event->name }}</p>
                                    <p class="text-sm text-slate-500">Pedido #{{ $order->id }} - {{ $order->created_at->format('d/m/Y') }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">Downloads</span>
                            </a>
                        @empty
                            <p class="py-6 text-sm text-slate-500">Voce ainda nao comprou fotos.</p>
                        @endforelse
                    </div>
                </section>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'Total de eventos', 'value' => $total_events],
                        ['label' => 'Fotos cadastradas', 'value' => $total_photos],
                        ['label' => 'Vendas', 'value' => $total_sales],
                        ['label' => 'Faturamento', 'value' => 'R$ '.number_format((float) $total_revenue, 2, ',', '.')],
                    ] as $card)
                        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $card['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
