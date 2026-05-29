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
        return $user->is_admin() || $event->user_id === $user->id || $event->member_for($user) !== null;
    }

    public function create(User $user): bool
    {
        return $user->is_photographer();
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->is_admin() || $event->user_id === $user->id) {
            return true;
        }

        return (bool) $event->member_for($user)?->can_manage_event();
    }

    public function delete(User $user, Event $event): bool
    {
        return ($user->is_admin() || $event->user_id === $user->id || (bool) $event->member_for($user)?->can_manage_event())
            && ! $event->orders()->exists();
    }

    public function uploadPhotos(User $user, Event $event): bool
    {
        if ($user->is_admin() || $event->user_id === $user->id) {
            return true;
        }

        return (bool) $event->member_for($user)?->can_upload_photos();
    }

    public function editPhotos(User $user, Event $event): bool
    {
        if ($user->is_admin() || $event->user_id === $user->id) {
            return true;
        }

        return (bool) $event->member_for($user)?->can_edit_photos();
    }

    public function manageMembers(User $user, Event $event): bool
    {
        if ($user->is_admin() || $event->user_id === $user->id) {
            return true;
        }

        return (bool) $event->member_for($user)?->can_manage_event();
    }
}
