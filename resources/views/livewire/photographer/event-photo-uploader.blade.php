<div class="space-y-8">
    <form
        wire:submit="upload_photos"
        x-data="{ upload_error: '' }"
        class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
    >
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="flex-1">
                <label class="text-sm font-semibold text-slate-800">Enviar fotos do evento</label>
                <input
                    type="file"
                    wire:model="photos"
                    multiple
                    accept=".jpg,.jpeg,.png,.webp"
                    x-on:change.capture="
                        if ($event.target.files.length > 20) {
                            upload_error = 'Envie no máximo 20 fotos por lote. Para mais fotos, envie em lotes separados.';
                            $event.target.value = '';
                            $event.stopImmediatePropagation();
                        } else {
                            upload_error = '';
                        }
                    "
                    class="mt-3 w-full rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-600"
                />
                <p x-show="upload_error" x-text="upload_error" class="mt-2 text-sm text-red-600"></p>
                @error('photos') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('photos.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-3 text-sm text-slate-500">Aceita JPG, PNG e WebP até 20MB por foto. Envie até 20 fotos por lote; a versão pública usa marca d'água.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Enviar fotos</button>
                <button type="button" wire:click="process_pending_photos" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Processar pendentes</button>
            </div>
        </div>
        <p wire:loading wire:target="photos,upload_photos" class="mt-3 text-sm text-slate-500">Processando envio...</p>
        @if (session('status')) <p class="mt-3 text-sm font-medium text-emerald-700">{{ session('status') }}</p> @endif
    </form>

    <form wire:submit="import_metadata_csv" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex-1">
                <label class="text-sm font-semibold text-slate-800">Importar busca por CSV</label>
                <input type="file" wire:model="metadata_csv" accept=".csv,.txt" class="mt-3 w-full rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-600" />
                @error('metadata_csv') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-3 text-sm text-slate-500">Use quando tiver número de peito, nome, equipe ou turma. Colunas aceitas: filename/foto/arquivo, número/código/dorsal, nome, equipe, turma, tags.</p>
            </div>
            <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Importar CSV</button>
        </div>
        <p wire:loading wire:target="metadata_csv,import_metadata_csv" class="mt-3 text-sm text-slate-500">Lendo arquivo...</p>
    </form>

    @if ($recent_batches->isNotEmpty())
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Lotes recentes</h2>
                    <p class="mt-1 text-sm text-slate-500">Acompanhe o processamento por envio. Para volumes grandes, envie vários lotes.</p>
                </div>
            </div>
            <div class="mt-5 grid gap-3 lg:grid-cols-3">
                @foreach ($recent_batches as $batch)
                    @php
                        $progress = $batch->total_files > 0 ? (($batch->processed_files + $batch->failed_files) / $batch->total_files) * 100 : 0;
                    @endphp
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $batch->uploader?->name ?? 'Equipe Fotx' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $batch->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $batch->status }}</span>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $progress }}%"></div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            {{ $batch->processed_files }} prontas,
                            {{ $batch->failed_files }} falhas,
                            {{ $batch->total_files }} no total
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap gap-2">
            @foreach ([
                '' => 'Todas',
                'uploaded' => 'Enviadas',
                'processing' => 'Processando',
                'ready' => 'Prontas',
                'failed' => 'Falhas',
            ] as $status => $label)
                <button
                    type="button"
                    wire:click="$set('status_filter', '{{ $status }}')"
                    class="rounded-2xl px-4 py-2 text-sm font-semibold {{ $status_filter === $status ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700' }}"
                >
                    {{ $label }}
                    @if ($status !== '')
                        <span class="ml-1 text-xs opacity-70">{{ $status_counts[$status] ?? 0 }}</span>
                    @endif
                </button>
            @endforeach
        </div>
        <p class="text-sm text-slate-500">{{ $event_photos->total() }} foto(s)</p>
    </div>

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
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800">{{ $event_photo->filename }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $event_photo->uploader?->name ? 'Enviada por '.$event_photo->uploader->name : 'Sem uploader' }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $event_photo->status }}</span>
                    </div>
                    <div class="space-y-2 rounded-xl bg-slate-50 p-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Número / código</label>
                            <input
                                wire:model="metadata.{{ $event_photo->id }}.participant_code"
                                class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                placeholder="Ex: 3087"
                            />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Tags de busca</label>
                            <input
                                wire:model="metadata.{{ $event_photo->id }}.search_keywords"
                                class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                placeholder="Nome, equipe, turma..."
                            />
                        </div>
                        <button wire:click="save_metadata({{ $event_photo->id }})" class="w-full rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">Salvar busca</button>
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

    <div>
        {{ $event_photos->links() }}
    </div>
</div>
