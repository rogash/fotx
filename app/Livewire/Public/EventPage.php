<?php

namespace App\Livewire\Public;

use App\Models\Event;
use Livewire\Component;

class EventPage extends Component
{
    public Event $event;

    public function mount(string $slug): void
    {
        $this->event = Event::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.public.event-page')->layout('layouts.public');
    }
}
