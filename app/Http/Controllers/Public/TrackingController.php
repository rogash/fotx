<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Services\EventAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function whatsapp(string $slug, Request $request, EventAnalyticsService $analytics): RedirectResponse
    {
        $event = Event::query()->where('slug', $slug)->firstOrFail();
        $event_photo = null;
        $event_photo_public_id = (string) $request->query('photo', '');

        if ($event_photo_public_id !== '') {
            $event_photo = $event
                ->photos()
                ->where('status', 'ready')
                ->where('public_id', $event_photo_public_id)
                ->first();
        }

        $analytics->record(
            event: $event,
            type: 'whatsapp_click',
            source: $event_photo ? 'photo' : 'event',
            event_photo: $event_photo,
            metadata: ['target' => $event_photo ? 'photo' : 'event'],
            request: $request,
        );

        return redirect()->away($this->whatsapp_url($event, $event_photo));
    }

    private function whatsapp_url(Event $event, ?EventPhoto $event_photo = null): string
    {
        $number = (string) config('fotx.whatsapp_number');
        $message = $event_photo
            ? 'Achei uma foto no Fotx: '.$event_photo->public_url()
            : (string) config('fotx.whatsapp_support_message').' Evento: '.$event->name.' - '.$event->public_url();

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
