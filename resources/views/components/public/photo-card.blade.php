@props(['event', 'photo', 'inCart' => false, 'score' => null])

<div wire:key="foto-{{ $photo->public_id }}" class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 {{ $inCart ? 'ring-2 ring-emerald-500' : 'ring-slate-200/80' }}">
    <a href="{{ route('public.photos.show', [$event->slug, $photo]) }}" class="relative block aspect-[4/3] bg-slate-100">
        <img src="{{ route('media.photos.watermarked', $photo) }}" loading="lazy" class="h-full w-full object-contain" alt="Foto do evento {{ $event->name }}">
        @if ($score !== null)
            <span class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-xs font-semibold text-emerald-700 shadow-sm">{{ number_format($score * 100, 0) }}% compatível</span>
        @endif
        @if ($photo->participant_code)
            <span class="absolute right-2 top-2 rounded-full bg-slate-950/70 px-2 py-0.5 text-xs font-semibold text-white">Nº {{ $photo->participant_code }}</span>
        @endif
    </a>
    <div class="p-2">
        @if ($inCart)
            <button type="button" title="Remover do carrinho" wire:click="remove_from_cart('{{ $photo->public_id }}')" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-red-50 hover:text-red-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                No carrinho
            </button>
        @else
            <button type="button" wire:click="add_to_cart('{{ $photo->public_id }}')" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-slate-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                Adicionar · R$ {{ number_format((float) $event->price_per_photo, 2, ',', '.') }}
            </button>
        @endif
    </div>
</div>
