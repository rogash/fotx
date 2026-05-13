<?php

namespace App\Http\Controllers\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Event::class);

        $events = Event::query()
            ->withCount(['photos', 'orders'])
            ->when(! Auth::user()->is_admin(), fn ($query) => $query->where('user_id', Auth::id()))
            ->latest()
            ->paginate(12);

        return view('photographer.events.index', compact('events'));
    }

    public function show(Event $event): View
    {
        $this->authorize('view', $event);

        $event->loadCount(['photos', 'ready_photos', 'orders']);

        return view('photographer.events.show', compact('event'));
    }

    public function create(): View
    {
        $this->authorize('create', Event::class);

        return view('photographer.events.create');
    }

    public function edit(Event $event): View
    {
        $this->authorize('update', $event);

        return view('photographer.events.edit', compact('event'));
    }

    public function photos(Event $event): View
    {
        $this->authorize('update', $event);

        $event->load(['photos' => fn ($query) => $query->latest()]);

        return view('photographer.events.photos', compact('event'));
    }

    public function publish(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->update(['status' => $event->status === 'published' ? 'draft' : 'published']);

        return back()->with('status', 'Status do evento atualizado.');
    }
}
