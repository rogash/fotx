<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAnalytic;
use App\Models\EventPhoto;
use App\Models\Order;
use Illuminate\Http\Request;

class EventAnalyticsService
{
    public function record(
        Event $event,
        string $type,
        ?string $source = null,
        ?EventPhoto $event_photo = null,
        ?Order $order = null,
        array $metadata = [],
        ?Request $request = null,
    ): EventAnalytic {
        $request ??= request();

        return EventAnalytic::query()->create([
            'event_id' => $event->id,
            'event_photo_id' => $event_photo?->id,
            'order_id' => $order?->id,
            'type' => $type,
            'source' => $source,
            'ip_hash' => $this->ip_hash($request),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    private function ip_hash(Request $request): ?string
    {
        $ip = $request->ip();

        return blank($ip) ? null : hash('sha256', $ip.'|'.config('app.key'));
    }
}
