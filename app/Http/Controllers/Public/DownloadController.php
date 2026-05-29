<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EventPhoto;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    public function __invoke(Order $order, EventPhoto $event_photo): Response
    {
        abort_unless($order->status === 'paid', 403);
        abort_unless($order->event_id === $event_photo->event_id, 403);
        abort_unless($order->has_photo($event_photo), 403);

        return Storage::disk(config('filesystems.default'))->download($event_photo->original_path, $event_photo->filename);
    }
}
