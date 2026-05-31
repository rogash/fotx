<x-guest-layout>
    <div>
        <h1 class="text-2xl font-bold text-slate-950">Downloads</h1>
        <div class="mt-6 space-y-4">
            @foreach ($order->items as $item)
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 p-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-16 w-20 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                            <img src="{{ route('media.photos.thumbnail', $item->event_photo) }}" class="h-full w-full object-contain" alt="{{ $item->event_photo->filename }}">
                        </span>
                        <p class="text-sm font-semibold text-slate-800">{{ $item->event_photo->filename }}</p>
                    </div>
                    <a href="{{ $download_links[$item->event_photo_id] }}" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Baixar foto</a>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-xs text-slate-500">Cada link de download expira em 15 minutos por segurança. Reabra esta página para gerar novos links.</p>
    </div>
</x-guest-layout>
