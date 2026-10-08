<?php

namespace App\Livewire\Public;

use App\Models\Event;
use App\Services\EventAnalyticsService;
use Livewire\Component;

class EventPage extends Component
{
    public Event $event;

    public bool $is_available = false;

    public function mount(string $slug): void
    {
        $this->event = Event::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $this->is_available = $this->event->status === 'published';

        if (! $this->is_available) {
            return;
        }

        $analytics = app(EventAnalyticsService::class);
        $analytics->record(
            event: $this->event,
            type: 'event_view',
            source: request()->query('via') === 'qr' ? 'qr' : 'direct',
            metadata: ['path' => request()->path()],
        );

        if (request()->query('via') === 'qr') {
            $analytics->record(
                event: $this->event,
                type: 'qr_view',
                source: 'qr',
                metadata: ['path' => request()->path()],
            );
        }
    }

    public function render()
    {
        if (! $this->is_available) {
            return view('livewire.public.event-unavailable')
                ->layout('layouts.public', ['title' => $this->event->name]);
        }

        $photos_count = $this->event->ready_photos()->count();

        return view('livewire.public.event-page', [
            'photos_count' => $photos_count,
            'discount_tiers' => config('fotx.cart_volume_discounts', []),
        ])->layout('layouts.public', [
            'title' => $this->event->name,
            'description' => "Encontre suas fotos do evento {$this->event->name} por selfie ou número e baixe em alta resolução.",
            'og_image' => $this->event->cover_photo?->watermarked_path
                ? route('media.photos.watermarked', $this->event->cover_photo)
                : null,
        ]);
    }
}
