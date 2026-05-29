<?php

namespace App\Livewire\Photographer;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EventForm extends Component
{
    public ?Event $event = null;

    public string $name = '';

    public string $slug = '';

    public ?string $event_date = null;

    public ?string $location = null;

    public ?string $description = null;

    public string $price_per_photo = '25.00';

    public string $status = 'draft';

    public function mount(?Event $event = null): void
    {
        if ($event?->exists) {
            $this->authorize('update', $event);
            $this->event = $event;
            $this->fill($event->only(['name', 'slug', 'event_date', 'location', 'description', 'price_per_photo', 'status']));
            $this->event_date = $event->event_date?->format('Y-m-d');
        } else {
            $this->authorize('create', Event::class);
        }
    }

    public function updatedName(): void
    {
        if (! $this->event?->exists || blank($this->slug)) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('events', 'slug')->ignore($this->event?->id)],
            'event_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_per_photo' => ['required', 'numeric', 'min:1', 'max:999999'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $event = Event::query()->updateOrCreate(
            ['id' => $this->event?->id],
            [...$validated, 'user_id' => $this->event?->user_id ?? Auth::id()]
        );

        if (! $this->event?->exists) {
            $event->members()->updateOrCreate(
                ['user_id' => Auth::id()],
                ['role' => 'owner'],
            );
        }

        $this->redirectRoute('events.show', $event, navigate: true);
    }

    public function render()
    {
        return view('livewire.photographer.event-form');
    }
}
