<?php

namespace App\Livewire\Photographer;

use App\Jobs\ProcessEventPhotoJob;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Services\PhotoProcessingService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class EventPhotoUploader extends Component
{
    use WithFileUploads;

    public Event $event;

    #[Validate(['photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:20480'])]
    public array $photos = [];

    public string $status_filter = '';

    public function upload(): void
    {
        $this->authorize('update', $this->event);
        $this->validate();

        foreach ($this->photos as $photo) {
            $path = $photo->store("events/{$this->event->id}/originals", config('filesystems.default'));
            $event_photo = EventPhoto::query()->create([
                'event_id' => $this->event->id,
                'original_path' => $path,
                'filename' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType() ?: 'image/jpeg',
                'size_bytes' => $photo->getSize(),
                'status' => 'uploaded',
            ]);

            ProcessEventPhotoJob::dispatch($event_photo);
        }

        $this->photos = [];
        $this->dispatch('photos-uploaded');
        session()->flash('status', 'Fotos enviadas para processamento.');
    }

    public function set_cover(int $event_photo_id): void
    {
        $this->authorize('update', $this->event);

        $event_photo = $this->event->photos()->where('status', 'ready')->findOrFail($event_photo_id);
        $this->event->update(['cover_photo_id' => $event_photo->id]);
        $this->event->refresh();

        session()->flash('status', 'Foto de capa atualizada.');
    }

    public function reprocess_photo(int $event_photo_id, PhotoProcessingService $photo_processing_service): void
    {
        $this->authorize('update', $this->event);

        $event_photo = $this->event->photos()->findOrFail($event_photo_id);
        $photo_processing_service->process($event_photo);

        session()->flash('status', 'Foto reprocessada.');
    }

    public function delete_photo(int $event_photo_id): void
    {
        $this->authorize('update', $this->event);

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

        session()->flash('status', 'Foto removida.');
    }

    public function render()
    {
        return view('livewire.photographer.event-photo-uploader', [
            'event_photos' => $this->event
                ->photos()
                ->when($this->status_filter !== '', fn ($query) => $query->where('status', $this->status_filter))
                ->latest()
                ->get(),
            'status_counts' => $this->event->photos()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
        ]);
    }
}
