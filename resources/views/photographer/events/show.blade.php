<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ $event->name }}</h1>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $event->status }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $event->location ?: 'Local não informado' }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @can('editPhotos', $event)
                    <a href="{{ route('events.photos', $event) }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Fotos</a>
                @endcan
                <a href="{{ route('events.orders', $event) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Pedidos</a>
                @can('update', $event)
                    <a href="{{ route('events.edit', $event) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Editar</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Divulgação do evento</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Compartilhe o evento com clientes</h2>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500">
                            Use o link, o QR Code ou o cartaz no local do evento. O cliente abre a galeria, busca por selfie ou número, compra e baixa online.
                        </p>
                        <p class="mt-4 break-all rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">{{ $event->public_url() }}</p>
                        @if ($event->status !== 'published')
                            <p class="mt-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                                Publique o evento para que o link e o QR Code funcionem para clientes.
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row lg:flex-col">
                        <div class="rounded-2xl border border-slate-200 bg-white p-3">
                            <img src="{{ route('events.qr-code', $event) }}" class="h-44 w-44" alt="QR Code do evento {{ $event->name }}">
                        </div>
                        <div class="grid gap-3">
                            <button
                                type="button"
                                x-data="{ copied: false }"
                                x-on:click="navigator.clipboard.writeText('{{ $event->public_url() }}'); copied = true; setTimeout(() => copied = false, 1800)"
                                class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white"
                            >
                                <span x-show="! copied">Copiar link público</span>
                                <span x-show="copied">Link copiado</span>
                            </button>
                            <a href="{{ route('events.qr-code', [$event, 'download' => 1]) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-slate-700">Baixar QR Code</a>
                            <a href="{{ route('events.poster', $event) }}" target="_blank" class="rounded-2xl border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-slate-700">Imprimir cartaz</a>
                            <a href="{{ route('public.events.show', $event->slug) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-slate-700">Ver como cliente</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-semibold text-slate-950">1. Envie fotos</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Suba as imagens do evento. O Fotx gera miniatura e versão com marca d'água.</p>
                    @can('editPhotos', $event)
                        <a href="{{ route('events.photos', $event) }}" class="mt-4 inline-flex text-sm font-bold text-emerald-700">Gerenciar fotos</a>
                    @endcan
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-semibold text-slate-950">2. Complete a busca</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Importe CSV com número, nome ou equipe para melhorar a busca pública.</p>
                    @can('editPhotos', $event)
                        <a href="{{ route('events.photos', $event) }}" class="mt-4 inline-flex text-sm font-bold text-emerald-700">Importar CSV</a>
                    @endcan
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-semibold text-slate-950">3. Publique e divulgue</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Publique o evento e compartilhe o QR Code quando a galeria estiver pronta.</p>
                    <a href="{{ route('events.poster', $event) }}" target="_blank" class="mt-4 inline-flex text-sm font-bold text-emerald-700">Abrir cartaz</a>
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

            <section class="grid gap-6 lg:grid-cols-[1fr_1fr]">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Equipe do evento</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">Fotógrafos e assistentes</h2>
                            <p class="mt-2 text-sm text-slate-500">Adicione pessoas cadastradas no Fotx para dividir upload, metadados e operação da galeria.</p>
                        </div>
                    </div>

                    @can('manageMembers', $event)
                        <form method="POST" action="{{ route('events.members.store', $event) }}" class="mt-5 grid gap-3 sm:grid-cols-[1fr_170px_auto]">
                            @csrf
                            <input name="email" type="email" required placeholder="fotografo@email.com" class="rounded-2xl border-slate-200 text-sm shadow-sm focus:border-slate-900 focus:ring-slate-900">
                            <select name="role" class="rounded-2xl border-slate-200 text-sm shadow-sm focus:border-slate-900 focus:ring-slate-900">
                                <option value="photographer">Fotógrafo</option>
                                <option value="assistant">Assistente</option>
                                <option value="viewer">Visualizador</option>
                            </select>
                            <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Adicionar</button>
                        </form>
                    @endcan

                    <div class="mt-5 divide-y divide-slate-100">
                        @foreach ($event->members as $member)
                            <div class="flex items-center justify-between gap-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $member->user->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $member->user->email }} - {{ $member->role }}</p>
                                </div>
                                @can('manageMembers', $event)
                                    @if ($member->role !== 'owner')
                                        <form method="POST" action="{{ route('events.members.destroy', [$event, $member]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm font-semibold text-red-600">Remover</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Volume de fotos</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">Últimos lotes</h2>
                        </div>
                        @can('editPhotos', $event)
                            <a href="{{ route('events.photos', $event) }}" class="text-sm font-semibold text-slate-700">Ver fotos</a>
                        @endcan
                    </div>
                    <div class="mt-5 divide-y divide-slate-100">
                        @forelse ($recent_batches as $batch)
                            <div class="py-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $batch->uploader?->name ?? 'Equipe Fotx' }}</p>
                                        <p class="text-sm text-slate-500">{{ $batch->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $batch->status }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">{{ $batch->processed_files }} prontas, {{ $batch->failed_files }} falhas, {{ $batch->total_files }} no total</p>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-slate-500">Nenhum lote enviado ainda.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Metricas do evento</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Funil público</h2>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500">
                            Acompanhe acessos, leituras de QR Code, buscas e interações que ajudam a vender as fotos.
                        </p>
                    </div>
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ([
                        ['label' => 'Acessos', 'value' => $analytics_counts['event_view'] ?? 0],
                        ['label' => 'Via QR Code', 'value' => $analytics_counts['qr_view'] ?? 0],
                        ['label' => 'Buscas por selfie', 'value' => $analytics_counts['selfie_search'] ?? 0],
                        ['label' => 'Buscas por texto', 'value' => $analytics_counts['text_search'] ?? 0],
                        ['label' => 'Cliques WhatsApp', 'value' => $analytics_counts['whatsapp_click'] ?? 0],
                    ] as $metric)
                        <div class="rounded-2xl bg-slate-50 p-5">
                            <p class="text-sm text-slate-500">{{ $metric['label'] }}</p>
                            <p class="mt-2 text-3xl font-semibold text-slate-950">{{ $metric['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-950">Publicação</h2>
                    <p class="mt-2 text-sm text-slate-500">Controle quando o evento aparece para clientes e proteja o histórico de vendas.</p>
                    @can('update', $event)
                        <div class="mt-6 flex flex-wrap gap-3">
                            <form method="POST" action="{{ route('events.publish', $event) }}">
                                @csrf
                                <button class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">{{ $event->status === 'published' ? 'Despublicar evento' : 'Publicar evento' }}</button>
                            </form>
                            @if ($event->status !== 'archived')
                                <form method="POST" action="{{ route('events.archive', $event) }}">
                                    @csrf
                                    <button class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Arquivar evento</button>
                                </form>
                            @endif
                        </div>
                    @else
                        <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Você faz parte da equipe deste evento, mas a publicação fica com o dono.</p>
                    @endcan

                    @can('delete', $event)
                        <div class="mt-6 rounded-2xl bg-red-50 p-4">
                            <h3 class="text-sm font-bold text-red-900">Zona de exclusão</h3>
                            @if ($event->orders_count > 0)
                                <p class="mt-2 text-sm text-red-700">Este evento tem pedidos. Por segurança, ele pode ser arquivado, mas não excluído definitivamente.</p>
                            @else
                                <p class="mt-2 text-sm text-red-700">Excluir remove o evento e os arquivos das fotos. Use apenas para testes ou eventos criados por engano.</p>
                                <form method="POST" action="{{ route('events.destroy', $event) }}" class="mt-4" onsubmit="return confirm('Excluir definitivamente este evento e suas fotos? Esta ação não pode ser desfeita.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-2xl border border-red-200 bg-white px-5 py-3 text-sm font-semibold text-red-700">Excluir definitivamente</button>
                                </form>
                            @endif
                        </div>
                    @endcan
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
