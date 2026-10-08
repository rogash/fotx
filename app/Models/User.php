<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'photographer_requested_at' => 'datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function event_memberships(): HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function uploaded_photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class, 'uploaded_by');
    }

    public function attributed_photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class, 'photographer_id');
    }

    public function is_admin(): bool
    {
        return $this->role === 'admin';
    }

    public function is_photographer(): bool
    {
        return in_array($this->role, ['admin', 'photographer'], true);
    }

    // O pedido fica pendente com o usuário ainda como cliente, então nenhuma
    // permissão de fotógrafo é concedida antes da aprovação de um admin.
    public function has_pending_photographer_request(): bool
    {
        return $this->role === 'customer' && $this->photographer_requested_at !== null;
    }

    public function request_photographer_access(?string $portfolio = null): void
    {
        $this->forceFill([
            'photographer_requested_at' => now(),
            'photographer_portfolio' => $portfolio,
        ])->save();
    }

    public function approve_photographer(): void
    {
        $this->forceFill([
            'role' => 'photographer',
            'photographer_requested_at' => null,
        ])->save();
    }

    public function reject_photographer(): void
    {
        $this->forceFill(['photographer_requested_at' => null])->save();
    }
}
