<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EventPhoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function thumbnail(EventPhoto $event_photo): Response
    {
        abort_if($event_photo->status !== 'ready' || blank($event_photo->thumbnail_path), 404);

        return Storage::disk(config('filesystems.default'))->response($event_photo->thumbnail_path);
    }

    public function watermarked(EventPhoto $event_photo): Response
    {
        abort_if($event_photo->status !== 'ready' || blank($event_photo->watermarked_path), 404);

        return Storage::disk(config('filesystems.default'))->response($event_photo->watermarked_path);
    }
}
