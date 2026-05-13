<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-slate-900">Eventos</h1>
            <a href="{{ route('events.create') }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Novo evento</a>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto grid max-w-7xl gap-5 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            @foreach ($events as $event)
                <a href="{{ route('events.show', $event) }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-950">{{ $event->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $event->location ?: 'Local nao informado' }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $event->status }}</span>
                    </div>
                    <div class="mt-6 flex items-center justify-between text-sm text-slate-600">
                        <span>{{ $event->photos_count }} fotos</span>
                        <span>R$ {{ number_format((float) $event->price_per_photo, 2, ',', '.') }}</span>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">{{ $events->links() }}</div>
    </div>
</x-app-layout>
