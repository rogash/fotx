<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-slate-900">Fotos de {{ $event->name }}</h1></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><livewire:photographer.event-photo-uploader :event="$event" /></div></div>
</x-app-layout>
