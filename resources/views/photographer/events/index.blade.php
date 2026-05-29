<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-950">Eventos</h1>
                <p class="mt-1 text-sm text-slate-500">Gerencie eventos ativos, rascunhos e arquivados.</p>
            </div>
            <a href="{{ route('events.create') }}" class="fotx-button-primary">Novo evento</a>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <p class="mb-5 rounded-[1.5rem] bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-100">{{ session('status') }}</p>
            @endif

            <div class="mb-6 flex flex-wrap gap-2">
                @foreach ([
                    '' => 'Todos',
                    'draft' => 'Rascunhos',
                    'published' => 'Publicados',
                    'archived' => 'Arquivados',
                ] as $status => $label)
                    <a
                        href="{{ $status === '' ? route('events.index') : route('events.index', ['status' => $status]) }}"
                        class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request('status', '') === $status ? 'bg-slate-950 text-white' : 'bg-white/75 text-slate-700 ring-1 ring-slate-200 hover:bg-white' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mx-auto grid max-w-7xl gap-5 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            @foreach ($events as $event)
                <a href="{{ route('events.show', $event) }}" class="fotx-card p-6 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-950">{{ $event->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $event->location ?: 'Local não informado' }}</p>
                        </div>
                        <span class="fotx-chip">{{ $event->status }}</span>
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
