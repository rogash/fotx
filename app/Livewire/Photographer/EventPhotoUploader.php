<?php

namespace App\Livewire\Photographer;

use App\Jobs\ProcessEventPhotoJob;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\PhotoBatch;
use App\Services\PhotoMetadataCsvImporter;
use App\Services\PhotoProcessingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EventPhotoUploader extends Component
{
    use WithFileUploads, WithPagination;

    public Event $event;

    #[Validate(['photos' => 'array|max:20', 'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:20480'])]
    public array $photos = [];

    public mixed $metadata_csv = null;

    public string $status_filter = '';

    public array $metadata = [];

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->load_metadata();
    }

    public function upload_photos(PhotoProcessingService $photo_processing_service): void
    {
        $this->authorize('uploadPhotos', $this->event);
        $this->validate();

        $upload_items = collect($this->photos)->map(function ($photo): array {
            return [
                'photo' => $photo,
                'filename' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType() ?: 'image/jpeg',
                'size_bytes' => $photo->getSize(),
                'file_hash' => hash_file('sha256', $photo->getRealPath()),
            ];
        });

        $existing_hashes = EventPhoto::query()
            ->where('event_id', $this->event->id)
            ->whereIn('file_hash', $upload_items->pluck('file_hash')->all())
            ->pluck('file_hash')
            ->all();

        $upload_items = $upload_items
            ->reject(fn (array $item): bool => in_array($item['file_hash'], $existing_hashes, true))
            ->values();

        if ($upload_items->isEmpty()) {
            $this->photos = [];
            session()->flash('status', 'Nenhuma foto nova enviada. Os arquivos selecionados já existem neste evento.');

            return;
        }

        $batch = PhotoBatch::query()->create([
            'event_id' => $this->event->id,
            'uploaded_by' => Auth::id(),
            'status' => 'uploading',
            'total_files' => $upload_items->count(),
            'original_total_bytes' => (int) $upload_items->sum('size_bytes'),
        ]);

        foreach ($upload_items as $item) {
            $photo = $item['photo'];
            $filename = $photo->getClientOriginalName();
            $path = $photo->store("events/{$this->event->id}/originals", config('filesystems.default'));
            $event_photo = EventPhoto::query()->create([
                'event_id' => $this->event->id,
                'photo_batch_id' => $batch->id,
                'uploaded_by' => Auth::id(),
                'photographer_id' => Auth::id(),
                'original_path' => $path,
                'filename' => $filename,
                'file_hash' => $item['file_hash'],
                'mime_type' => $item['mime_type'],
                'size_bytes' => $item['size_bytes'],
                'status' => 'uploaded',
            ]);

            if (config('fotx.process_photos_sync')) {
                $photo_processing_service->process($event_photo);
            } else {
                ProcessEventPhotoJob::dispatch($event_photo);
            }
        }

        $batch->refresh_progress();
        $this->photos = [];
        $this->load_metadata();
        $this->dispatch('photos-uploaded');
        session()->flash('status', config('fotx.process_photos_sync') ? 'Lote enviado e processado.' : 'Lote enviado para processamento.');
    }

    public function set_cover(int $event_photo_id): void
    {
        $this->authorize('editPhotos', $this->event);

        $event_photo = $this->event->photos()->where('status', 'ready')->findOrFail($event_photo_id);
        $this->event->update(['cover_photo_id' => $event_photo->id]);
        $this->event->refresh();

        session()->flash('status', 'Foto de capa atualizada.');
    }

    public function reprocess_photo(int $event_photo_id, PhotoProcessingService $photo_processing_service): void
    {
        $this->authorize('editPhotos', $this->event);

        $event_photo = $this->event->photos()->findOrFail($event_photo_id);
        $photo_processing_service->process($event_photo);

        session()->flash('status', 'Foto reprocessada.');
    }

    public function process_pending_photos(PhotoProcessingService $photo_processing_service): void
    {
        $this->authorize('editPhotos', $this->event);

        $pending_photos = $this->event->photos()
            ->whereIn('status', ['uploaded', 'failed'])
            ->get();

        foreach ($pending_photos as $event_photo) {
            $photo_processing_service->process($event_photo);
        }

        $this->event->refresh();
        session()->flash('status', "{$pending_photos->count()} foto(s) pendente(s) processada(s).");
    }

    public function delete_photo(int $event_photo_id): void
    {
        $this->authorize('editPhotos', $this->event);

        $event_photo = $this->event->photos()->findOrFail($event_photo_id);

        if ($this->event->cover_photo_id === $event_photo->id) {
            $this->event->update(['cover_photo_id' => null]);
        }

        Storage::disk(config('filesystems.default'))->delete(array_filter([
            $event_photo->original_path,
            $event_photo->thumbnail_path,
            $event_photo->watermarked_path,
        ]));

        $event_photo->delete();
        $this->event->refresh();
        unset($this->metadata[$event_photo_id]);

        session()->flash('status', 'Foto removida.');
    }

    public function save_metadata(int $event_photo_id): void
    {
        $this->authorize('editPhotos', $this->event);

        $event_photo = $this->event->photos()->findOrFail($event_photo_id);
        $data = validator($this->metadata[$event_photo_id] ?? [], [
            'participant_code' => ['nullable', 'string', 'max:80'],
            'search_keywords' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $event_photo->update([
            'participant_code' => blank($data['participant_code'] ?? null) ? null : trim((string) $data['participant_code']),
            'search_keywords' => blank($data['search_keywords'] ?? null) ? null : trim((string) $data['search_keywords']),
        ]);

        $this->load_metadata();
        session()->flash('status', 'Dados de busca salvos.');
    }

    public function import_metadata_csv(PhotoMetadataCsvImporter $importer): void
    {
        $this->authorize('editPhotos', $this->event);

        $this->validate([
            'metadata_csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $summary = $importer->import($this->event, $this->metadata_csv->getRealPath());

        $this->metadata_csv = null;
        $this->load_metadata();

        session()->flash(
            'status',
            "CSV importado: {$summary['imported_rows']} foto(s) atualizada(s), {$summary['skipped_rows']} linha(s) ignorada(s)."
        );
    }

    private function load_metadata(): void
    {
        $this->metadata = $this->event->photos()
            ->get(['id', 'participant_code', 'search_keywords'])
            ->mapWithKeys(fn (EventPhoto $event_photo): array => [
                $event_photo->id => [
                    'participant_code' => $event_photo->participant_code ?? '',
                    'search_keywords' => $event_photo->search_keywords ?? '',
                ],
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.photographer.event-photo-uploader', [
            'event_photos' => $this->event
                ->photos()
                ->with(['uploader', 'batch'])
                ->when($this->status_filter !== '', fn ($query) => $query->where('status', $this->status_filter))
                ->latest()
                ->paginate(24),
            'status_counts' => $this->event->photos()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
            'recent_batches' => $this->event->batches()
                ->with('uploader')
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
