<?php

namespace App\Http\Controllers\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Order;
use App\Models\User;
use App\Services\EventQrCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Event::class);

        $events = Event::query()
            ->withCount(['photos', 'orders'])
            ->visibleTo(Auth::user())
            ->when(request('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(12);

        return view('photographer.events.index', compact('events'));
    }

    public function show(Event $event): View
    {
        $this->authorize('view', $event);

        $event->load('members.user')->loadCount(['photos', 'ready_photos', 'orders']);
        $paid_orders = $event->orders()->where('status', 'paid');
        $recent_orders = $event->orders()->withCount('items')->latest()->limit(5)->get();
        $recent_batches = $event->batches()->with('uploader')->latest()->limit(5)->get();
        $analytics_counts = $event->analytics()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        return view('photographer.events.show', [
            'event' => $event,
            'paid_orders_count' => (clone $paid_orders)->count(),
            'event_revenue' => (clone $paid_orders)->sum('total_amount'),
            'recent_orders' => $recent_orders,
            'recent_batches' => $recent_batches,
            'analytics_counts' => $analytics_counts,
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
        $this->authorize('editPhotos', $event);

        $event->load(['photos' => fn ($query) => $query->with('uploader')->latest()]);

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

    public function qr_code(Event $event, Request $request, EventQrCodeService $event_qr_code_service): Response
    {
        $this->authorize('view', $event);

        $headers = [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ];

        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="'.$event_qr_code_service->filename($event).'"';
        }

        return response($event_qr_code_service->svg_for_event($event), 200, $headers);
    }

    public function poster(Event $event): View
    {
        $this->authorize('view', $event);

        return view('photographer.events.poster', [
            'event' => $event,
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

    public function add_member(Event $event, Request $request): RedirectResponse
    {
        $this->authorize('manageMembers', $event);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:photographer,assistant,viewer'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! $user->is_photographer()) {
            return back()->with('status', 'Convide apenas usuários fotógrafos já cadastrados no Fotx.');
        }

        if ((int) $user->id === (int) $event->user_id) {
            return back()->with('status', 'Este usuário já é o dono do evento.');
        }

        $event->members()->updateOrCreate(
            ['user_id' => $user->id],
            ['role' => $validated['role']]
        );

        return back()->with('status', 'Membro adicionado à equipe do evento.');
    }

    public function remove_member(Event $event, EventMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $event);
        abort_unless($member->event_id === $event->id, 404);
        abort_if($member->role === 'owner', 403);

        $member->delete();

        return back()->with('status', 'Membro removido da equipe.');
    }

    public function archive(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->update(['status' => 'archived']);

        return redirect()->route('events.show', $event)->with('status', 'Evento arquivado. Ele não aparece mais para clientes.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $disk = Storage::disk(config('filesystems.default'));
        $event->photos()
            ->get(['original_path', 'thumbnail_path', 'watermarked_path'])
            ->each(function ($event_photo) use ($disk): void {
                $disk->delete(array_filter([
                    $event_photo->original_path,
                    $event_photo->thumbnail_path,
                    $event_photo->watermarked_path,
                ]));
            });

        $event->delete();

        return redirect()->route('events.index')->with('status', 'Evento excluído definitivamente.');
    }
}
