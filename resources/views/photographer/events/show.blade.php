<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">{{ $event->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $event->public_url() }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('events.photos', $event) }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Fotos</a>
                <a href="{{ route('events.edit', $event) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Editar</a>
            </div>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-5 md:grid-cols-3">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Fotos</p><p class="mt-2 text-3xl font-semibold">{{ $event->photos_count }}</p></div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Prontas</p><p class="mt-2 text-3xl font-semibold">{{ $event->ready_photos_count }}</p></div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Pedidos</p><p class="mt-2 text-3xl font-semibold">{{ $event->orders_count }}</p></div>
            </div>
            <form method="POST" action="{{ route('events.publish', $event) }}" class="mt-6">
                @csrf
                <button class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">{{ $event->status === 'published' ? 'Despublicar evento' : 'Publicar evento' }}</button>
                <a href="{{ route('public.events.show', $event->slug) }}" class="ml-3 rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Abrir link publico</a>
            </form>
        </div>
    </div>
</x-app-layout>
