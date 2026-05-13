<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EventPhoto;
use App\Models\Order;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __invoke(Order $order, string $download_token, EventPhoto $event_photo): Response
    {
        abort_unless(hash_equals((string) $order->download_token, $download_token), 403);
        abort_unless($order->status === 'paid', 403);
        abort_unless($order->event_id === $event_photo->event_id, 403);
        abort_unless($order->has_photo($event_photo), 403);

        return Storage::disk(config('filesystems.default'))->download($event_photo->original_path, $event_photo->filename);
    }
}
