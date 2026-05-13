<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'event_photo_id', 'face_box', 'embedding', 'confidence'])]
class PhotoFace extends Model
{
    protected function casts(): array
    {
        return [
            'face_box' => 'array',
            'embedding' => 'array',
            'confidence' => 'decimal:4',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function event_photo(): BelongsTo
    {
        return $this->belongsTo(EventPhoto::class);
    }
}
