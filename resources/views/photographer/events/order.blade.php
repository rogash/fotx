<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Pedido #{{ $order->id }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $event->name }} - {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <a href="{{ route('events.orders', $event) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Voltar aos pedidos</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
            <section class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-950">Comprador</h2>
                    <div class="mt-5 space-y-3 text-sm">
                        <div>
                            <p class="text-slate-500">Nome</p>
                            <p class="font-semibold text-slate-900">{{ $order->buyer_name ?: 'Sem nome' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Email</p>
                            <p class="font-semibold text-slate-900">{{ $order->buyer_email }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-950">Pagamento</h2>
                    <dl class="mt-5 grid gap-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Status</dt>
                            <dd class="font-semibold text-slate-900">{{ $order->status }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Total</dt>
                            <dd class="font-semibold text-slate-900">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Provedor</dt>
                            <dd class="font-semibold text-slate-900">{{ $order->payment_provider ?: 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Referencia</dt>
                            <dd class="font-semibold text-slate-900">{{ $order->payment_reference ?: 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-950">Link de download</h2>
                    <p class="mt-2 break-all text-sm text-slate-600">{{ route('orders.downloads', [$order, $order->download_token]) }}</p>
                    <a href="{{ route('orders.downloads', [$order, $order->download_token]) }}" class="mt-5 inline-flex rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Abrir downloads</a>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold text-slate-950">Fotos compradas</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600">{{ $order->items->count() }} item(ns)</span>
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($order->items as $item)
                        <div class="rounded-2xl border border-slate-200 p-3">
                            <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100">
                                @if ($item->event_photo->thumbnail_path)
                                    <img src="{{ route('media.photos.thumbnail', $item->event_photo) }}" class="h-full w-full object-cover" alt="{{ $item->event_photo->filename }}">
                                @endif
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $item->event_photo->filename }}</p>
                                <p class="text-sm font-semibold text-slate-700">R$ {{ number_format((float) $item->price, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
