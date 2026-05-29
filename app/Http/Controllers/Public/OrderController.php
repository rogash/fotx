<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;

class OrderController extends Controller
{
    public function success(Order $order, string $download_token): View
    {
        $this->authorize_token($order, $download_token);

        return view('orders.success', [
            'order' => $order->load('items.event_photo'),
            'download_token' => $download_token,
        ]);
    }

    public function pending(Order $order, string $download_token): View
    {
        abort_unless(hash_equals((string) $order->download_token, $download_token), 403);

        return view('orders.pending', [
            'order' => $order->load('items.event_photo'),
            'download_token' => $download_token,
        ]);
    }

    public function downloads(Order $order, string $download_token): View
    {
        $this->authorize_token($order, $download_token);
        $order->load('items.event_photo');

        return view('orders.downloads', [
            'order' => $order,
            'download_token' => $download_token,
            'download_links' => $order->items
                ->mapWithKeys(fn ($item): array => [
                    $item->event_photo_id => URL::temporarySignedRoute(
                        'orders.download',
                        now()->addMinutes(15),
                        [$order, $item->event_photo],
                    ),
                ])
                ->all(),
        ]);
    }

    private function authorize_token(Order $order, string $download_token): void
    {
        abort_unless(hash_equals((string) $order->download_token, $download_token), 403);
        abort_unless($order->status === 'paid', 403);
    }
}
