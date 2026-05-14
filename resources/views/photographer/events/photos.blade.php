<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Fotos de {{ $event->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Envie, filtre e gerencie fotos processadas.</p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Voltar ao evento</a>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <livewire:photographer.event-photo-uploader :event="$event" />
        </div>
    </div>
</x-app-layout>
