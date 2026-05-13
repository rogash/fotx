<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'selfie_path', 'status', 'results', 'consent_accepted', 'ip_address', 'user_agent'])]
class FaceSearch extends Model
{
    protected function casts(): array
    {
        return [
            'results' => 'array',
            'consent_accepted' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
