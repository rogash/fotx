<div class="space-y-8">
    <form wire:submit="upload" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="flex-1">
                <label class="text-sm font-semibold text-slate-800">Upload múltiplo de fotos</label>
                <input type="file" wire:model="photos" multiple accept=".jpg,.jpeg,.png,.webp" class="mt-3 w-full rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-600" />
                @error('photos.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Enviar fotos</button>
        </div>
        <p wire:loading wire:target="photos,upload" class="mt-3 text-sm text-slate-500">Processando envio...</p>
        @if (session('status')) <p class="mt-3 text-sm font-medium text-emerald-700">{{ session('status') }}</p> @endif
    </form>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($event_photos as $event_photo)
            <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100">
                    @if ($event_photo->thumbnail_path)
                        <img src="{{ route('media.photos.thumbnail', $event_photo) }}" class="h-full w-full object-cover" alt="{{ $event_photo->filename }}">
                    @endif
                </div>
                <div class="mt-3 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $event_photo->filename }}</p>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $event_photo->status }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button wire:click="set_cover({{ $event_photo->id }})" @disabled($event_photo->status !== 'ready') class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-semibold text-slate-700 disabled:opacity-40">
                            {{ $event->cover_photo_id === $event_photo->id ? 'Capa atual' : 'Capa' }}
                        </button>
                        <button wire:click="reprocess_photo({{ $event_photo->id }})" class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-semibold text-slate-700">Refazer</button>
                        <button wire:click="delete_photo({{ $event_photo->id }})" wire:confirm="Remover esta foto?" class="rounded-xl border border-red-200 px-2 py-2 text-xs font-semibold text-red-600">Remover</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-6 text-sm text-slate-500 shadow-sm ring-1 ring-slate-200">Nenhuma foto enviada ainda.</div>
        @endforelse
    </div>
</div>
