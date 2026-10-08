<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-semibold text-slate-900">Pedidos de fotógrafos</h1>
        <p class="mt-1 text-sm text-slate-500">Aprove quem pode criar eventos e vender fotos no Fotx.</p>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <livewire:admin.photographer-requests />
        </div>
    </div>
</x-app-layout>
