<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-950">
                    {{ $role === 'customer' ? 'Minhas fotos' : 'Painel Fotx' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $role === 'admin' ? 'Visão geral da plataforma' : ($role === 'photographer' ? 'Gestão dos seus eventos e vendas' : 'Encontre eventos, acompanhe compras e baixe suas fotos') }}
                </p>
            </div>
            @if ($role !== 'customer')
                <a href="{{ route('events.create') }}" class="fotx-button-primary">Novo evento</a>
            @else
                <a href="{{ route('cart.show') }}" class="fotx-button-primary">Ver carrinho</a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-100">{{ session('status') }}</p>
            @endif

            @if ($role === 'customer')
                @if (auth()->user()->has_pending_photographer_request())
                    <section class="fotx-card mb-8 p-6">
                        <p class="text-sm font-semibold text-amber-700">Cadastro de fotógrafo em análise</p>
                        <p class="mt-1 text-sm text-slate-600">Recebemos seu pedido em {{ auth()->user()->photographer_requested_at->format('d/m/Y') }}. Assim que for aprovado, a área de eventos aparece aqui no painel.</p>
                    </section>
                @endif

                <div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
                    <section class="overflow-hidden rounded-[2rem] bg-slate-950 p-8 text-white shadow-sm">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-300">Área do cliente</p>
                        <h2 class="mt-4 max-w-2xl text-4xl font-extrabold leading-tight">Encontre suas fotos usando uma selfie.</h2>
                        <p class="mt-4 max-w-xl text-slate-300">Acesse um evento publicado, envie sua selfie com consentimento e finalize a compra das fotos encontradas.</p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            @forelse ($featured_events as $event)
                                <a href="{{ route('public.events.show', $event->slug) }}" class="rounded-full bg-white px-5 py-3 text-sm font-bold text-slate-950">{{ $event->name }}</a>
                            @empty
                                <span class="rounded-full bg-white/10 px-5 py-3 text-sm font-semibold text-slate-200">Nenhum evento publicado ainda</span>
                            @endforelse
                        </div>
                    </section>

                    <section class="fotx-card p-6">
                        <p class="text-sm font-medium text-slate-500">Compras realizadas</p>
                        <p class="mt-3 text-4xl font-semibold text-slate-950">{{ $customer_orders_count }}</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('cart.show') }}" class="fotx-button-secondary">Abrir carrinho</a>
                            <a href="{{ route('customer.orders.index') }}" class="fotx-button-primary">Minhas compras</a>
                        </div>
                    </section>
                </div>

                @unless (auth()->user()->has_pending_photographer_request())
                    <section class="fotx-card mt-8 flex flex-col gap-4 p-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-950">Vende fotos de eventos?</h2>
                            <p class="mt-1 text-sm text-slate-500">Peça acesso de fotógrafo para criar eventos e vender pelo Fotx.</p>
                        </div>
                        <form method="POST" action="{{ route('photographer.request') }}" class="flex w-full flex-col gap-2 sm:flex-row md:w-auto">
                            @csrf
                            <input type="text" name="portfolio" maxlength="255" placeholder="Portfólio ou Instagram (opcional)" class="fotx-input w-full px-4 py-2.5 text-sm sm:w-64" />
                            <button class="fotx-button-primary shrink-0">Pedir acesso</button>
                        </form>
                    </section>
                @endunless

                <section class="fotx-card mt-8 p-6">
                    <h2 class="text-lg font-semibold text-slate-950">Últimas compras</h2>
                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse ($customer_orders as $order)
                            <a href="{{ route('orders.downloads', [$order, $order->download_token]) }}" class="flex items-center justify-between gap-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $order->event->name }}</p>
                                    <p class="text-sm text-slate-500">Pedido {{ $order->public_id }} - {{ $order->created_at->format('d/m/Y') }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">Downloads</span>
                            </a>
                        @empty
                            <p class="py-6 text-sm text-slate-500">Você ainda não comprou fotos.</p>
                        @endforelse
                    </div>
                </section>
            @else
                @php
                    $latest_event = $recent_events->first();
                    $onboarding_steps = [
                        $total_events > 0,
                        $total_photos > 0,
                        $published_event !== null,
                        $total_sales > 0,
                    ];
                    $completed_steps = count(array_filter($onboarding_steps));
                @endphp

                @if ($role === 'photographer' && $completed_steps < count($onboarding_steps))
                    <section class="fotx-card mb-8 p-6">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-emerald-700">Primeiros passos</p>
                                <h2 class="mt-1 text-xl font-semibold text-slate-950">Coloque seu evento à venda</h2>
                            </div>
                            <p class="text-sm font-medium text-slate-500">{{ $completed_steps }} de {{ count($onboarding_steps) }} concluídos</p>
                        </div>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <x-photographer.step number="1" title="Crie um evento" :done="$onboarding_steps[0]" :href="route('events.create')" action="Criar evento">
                                Nome, data, local e preço por foto. Você pode ajustar tudo depois.
                            </x-photographer.step>
                            <x-photographer.step number="2" title="Envie as fotos" :done="$onboarding_steps[1]" :href="$latest_event ? route('events.photos', $latest_event) : null" action="Enviar fotos">
                                O Fotx gera as miniaturas, aplica a marca d'água e prepara a busca por selfie.
                            </x-photographer.step>
                            <x-photographer.step number="3" title="Publique o evento" :done="$onboarding_steps[2]" :href="$latest_event ? route('events.show', $latest_event) : null" action="Abrir evento">
                                Com o evento publicado, o link e o QR Code passam a funcionar para os clientes.
                            </x-photographer.step>
                            <x-photographer.step number="4" title="Divulgue e venda" :done="$onboarding_steps[3]" :href="$published_event ? route('events.poster', $published_event) : null" action="Imprimir cartaz" new_tab>
                                Compartilhe o link e o cartaz com QR Code para os participantes acharem as fotos.
                            </x-photographer.step>
                        </div>
                    </section>
                @endif

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'Eventos', 'value' => $total_events],
                        ['label' => 'Fotos enviadas', 'value' => $total_photos],
                        ['label' => 'Vendas', 'value' => $total_sales],
                        ['label' => 'Faturamento', 'value' => 'R$ '.number_format((float) $total_revenue, 2, ',', '.')],
                    ] as $card)
                        <div class="fotx-card p-6">
                            <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $card['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                    <section class="fotx-card p-6">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-lg font-semibold text-slate-950">Eventos recentes</h2>
                            @if ($total_events > 0)
                                <a href="{{ route('events.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">Ver todos</a>
                            @endif
                        </div>
                        <div class="mt-4 divide-y divide-slate-100">
                            @forelse ($recent_events as $event)
                                <a href="{{ route('events.show', $event) }}" class="flex items-center justify-between gap-4 py-4 hover:opacity-80">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $event->name }}</p>
                                        <p class="text-sm text-slate-500">{{ $event->event_date?->format('d/m/Y') ?? 'Sem data' }} · {{ $event->photos_count }} {{ $event->photos_count === 1 ? 'foto' : 'fotos' }}</p>
                                    </div>
                                    <x-status-badge type="event" :status="$event->status" />
                                </a>
                            @empty
                                <div class="py-6">
                                    <p class="text-sm text-slate-500">Você ainda não tem eventos.</p>
                                    <a href="{{ route('events.create') }}" class="fotx-button-primary mt-4">Criar primeiro evento</a>
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="fotx-card p-6">
                        <h2 class="text-lg font-semibold text-slate-950">Últimas vendas</h2>
                        <div class="mt-4 divide-y divide-slate-100">
                            @forelse ($recent_sales as $order)
                                <div class="flex items-center justify-between gap-4 py-4">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $order->event->name }}</p>
                                        <p class="text-sm text-slate-500">{{ ($order->paid_at ?? $order->created_at)->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <p class="shrink-0 font-semibold text-slate-950">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</p>
                                </div>
                            @empty
                                <p class="py-6 text-sm text-slate-500">Nenhuma venda ainda. As vendas aparecem aqui assim que o pagamento é aprovado.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
