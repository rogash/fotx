<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PhotographerRequests extends Component
{
    public function approve(int $user_id): void
    {
        $this->pending_user($user_id)->approve_photographer();
        session()->flash('status', 'Acesso de fotógrafo liberado.');
    }

    public function reject(int $user_id): void
    {
        $this->pending_user($user_id)->reject_photographer();
        session()->flash('status', 'Pedido recusado. A conta continua como cliente.');
    }

    private function pending_user(int $user_id): User
    {
        abort_unless(Auth::user()?->is_admin(), 403);

        return User::query()
            ->where('role', 'customer')
            ->whereNotNull('photographer_requested_at')
            ->findOrFail($user_id);
    }

    public function render()
    {
        abort_unless(Auth::user()?->is_admin(), 403);

        return view('livewire.admin.photographer-requests', [
            'pending_users' => User::query()
                ->where('role', 'customer')
                ->whereNotNull('photographer_requested_at')
                ->oldest('photographer_requested_at')
                ->get(['id', 'name', 'email', 'photographer_portfolio', 'photographer_requested_at']),
        ]);
    }
}
