<?php

namespace App\Livewire\Public;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EventCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $term = trim(mb_substr($this->search, 0, 100));

        $events = Event::query()
            ->inCatalog()
            ->with(['cover_photo', 'photographer:id,name'])
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '\\%_').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('location', 'like', $like));
            })
            ->orderByRaw('event_date is null')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(24, pageName: 'pagina');

        return view('livewire.public.event-catalog', [
            'events' => $events,
            'term' => $term,
        ])->layout('layouts.public', [
            'title' => 'Encontre o seu evento',
            'description' => 'Busque o evento em que você participou e encontre suas fotos por selfie no Fotx.',
        ]);
    }
}
