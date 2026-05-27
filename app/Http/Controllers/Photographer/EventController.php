<?php

namespace App\Http\Controllers\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $paid_orders = $event->orders()->where('status', 'paid');
        $recent_orders = $event->orders()->withCount('items')->latest()->limit(5)->get();

        return view('photographer.events.show', [
            'event' => $event,
            'paid_orders_count' => (clone $paid_orders)->count(),
            'event_revenue' => (clone $paid_orders)->sum('total_amount'),
            'recent_orders' => $recent_orders,
        ]);
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

    public function orders(Event $event): View
    {
        $this->authorize('view', $event);

        $orders = Order::query()
            ->with(['items.event_photo'])
            ->where('event_id', $event->id)
            ->latest()
            ->paginate(15);

        return view('photographer.events.orders', [
            'event' => $event,
            'orders' => $orders,
            'paid_orders_count' => $event->orders()->where('status', 'paid')->count(),
            'event_revenue' => $event->orders()->where('status', 'paid')->sum('total_amount'),
        ]);
    }

    public function order(Event $event, Order $order): View
    {
        $this->authorize('view', $event);
        abort_unless($order->event_id === $event->id, 404);

        return view('photographer.events.order', [
            'event' => $event,
            'order' => $order->load(['items.event_photo']),
        ]);
    }

    public function export_orders(Event $event): StreamedResponse
    {
        $this->authorize('view', $event);

        $filename = 'fotx-pedidos-'.$event->slug.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($event): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['pedido_id', 'comprador_nome', 'comprador_email', 'status', 'fotos', 'total', 'provedor', 'referencia', 'criado_em']);

            Order::query()
                ->withCount('items')
                ->where('event_id', $event->id)
                ->latest()
                ->chunk(200, function ($orders) use ($handle): void {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->id,
                            $order->buyer_name,
                            $order->buyer_email,
                            $order->status,
                            $order->items_count,
                            number_format((float) $order->total_amount, 2, '.', ''),
                            $order->payment_provider,
                            $order->payment_reference,
                            $order->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function publish(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->update(['status' => $event->status === 'published' ? 'draft' : 'published']);

        return back()->with('status', 'Status do evento atualizado.');
    }
}
