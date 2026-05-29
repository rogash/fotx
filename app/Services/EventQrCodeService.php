<?php

namespace App\Services;

use App\Models\Event;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class EventQrCodeService
{
    public function svg_for_url(string $url, int $size = 640): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(size: $size, margin: 2),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($url);
    }

    public function svg_for_event(Event $event, int $size = 640): string
    {
        return $this->svg_for_url($event->public_qr_url(), $size);
    }

    public function filename(Event $event): string
    {
        return 'fotx-qr-'.$event->slug.'.svg';
    }
}
