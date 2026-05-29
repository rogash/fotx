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
        return view($this->is_available ? 'livewire.public.event-page' : 'livewire.public.event-unavailable')
            ->layout('layouts.public');
    }
}
