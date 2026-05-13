<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        if (! $user->is_photographer()) {
            return view('dashboard', [
                'role' => 'customer',
                'featured_events' => Event::query()
                    ->where('status', 'published')
                    ->latest()
                    ->limit(3)
                    ->get(),
                'customer_orders' => Order::query()
                    ->with('event')
                    ->where('buyer_email', $user->email)
                    ->latest()
                    ->limit(5)
                    ->get(),
                'customer_orders_count' => Order::query()->where('buyer_email', $user->email)->count(),
            ]);
        }

        $events_query = Event::query()->when(! $user->is_admin(), fn ($query) => $query->where('user_id', $user->id));

        $event_ids = (clone $events_query)->pluck('id');
        $paid_orders = Order::query()->whereIn('event_id', $event_ids)->where('status', 'paid');

        return view('dashboard', [
            'role' => $user->role,
            'total_events' => (clone $events_query)->count(),
            'total_photos' => EventPhoto::query()->whereIn('event_id', $event_ids)->count(),
            'total_sales' => (clone $paid_orders)->count(),
            'total_revenue' => (clone $paid_orders)->sum('total_amount'),
        ]);
    }
}
