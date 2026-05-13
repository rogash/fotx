<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with(['event', 'items.event_photo'])
            ->where('buyer_email', Auth::user()->email)
            ->latest()
            ->paginate(12);

        return view('customer.orders.index', compact('orders'));
    }
}
