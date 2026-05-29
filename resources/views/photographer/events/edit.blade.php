<x-app-layout>
    <x-slot name="header">
        <h1 class="text-3xl font-semibold text-slate-950">Editar evento</h1>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <livewire:photographer.event-form :event="$event" />
        </div>
    </div>
</x-app-layout>
