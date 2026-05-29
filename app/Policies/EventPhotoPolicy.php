<?php

namespace App\Policies;

use App\Models\EventPhoto;
use App\Models\User;

class EventPhotoPolicy
{
    public function view(User $user, EventPhoto $event_photo): bool
    {
        return $user->can('view', $event_photo->event);
    }

    public function create(User $user): bool
    {
        return $user->is_photographer();
    }
}
