<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_photographer();
    }

    public function view(User $user, Event $event): bool
    {
        return $user->is_admin() || $event->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->is_photographer();
    }

    public function update(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }
}
