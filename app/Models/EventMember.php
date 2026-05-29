<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'user_id', 'role', 'permissions'])]
class EventMember extends Model
{
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function can_manage_event(): bool
    {
        return in_array($this->role, ['owner'], true);
    }

    public function can_upload_photos(): bool
    {
        return in_array($this->role, ['owner', 'photographer'], true);
    }

    public function can_edit_photos(): bool
    {
        return in_array($this->role, ['owner', 'photographer', 'assistant'], true);
    }
}
