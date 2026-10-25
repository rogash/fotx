@props(['selfie' => null])

<label class="relative mx-auto flex h-40 w-40 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-slate-300 bg-slate-50 text-center transition hover:border-emerald-500 hover:bg-emerald-50/50">
    @if ($selfie)
        <img src="{{ $selfie->temporaryUrl() }}" class="absolute inset-0 h-full w-full object-cover" alt="Sua selfie">
    @else
        <svg class="h-9 w-9 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A2.5 2.5 0 0 1 6.5 6h1.2l1.1-1.6A1 1 0 0 1 9.6 4h4.8a1 1 0 0 1 .8.4L16.3 6h1.2A2.5 2.5 0 0 1 20 8.5v8a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-8Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
        <span class="mt-2 px-4 text-sm font-semibold text-slate-700">Tirar ou escolher selfie</span>
    @endif
    <input type="file" wire:model="selfie" accept="image/jpeg,image/png,image/webp" capture="user" class="sr-only" />
    <span wire:loading wire:target="selfie" class="absolute inset-0 flex items-center justify-center bg-white/80 text-sm font-semibold text-slate-600">Carregando...</span>
</label>
