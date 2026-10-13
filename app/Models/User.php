<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        ];
    }

    // ------------------------------------------------------------------
    // RBAC Role Helpers
    // ------------------------------------------------------------------

    /** Admin: full access (manage users, approve, decline, delete, analytics). */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Moderator: approve, decline, view analytics. */
    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }

    /** Viewer: read-only dashboard access. */
    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /** Check if the user can approve/decline ads. */
    public function canModerate(): bool
    {
        return $this->isModerator();
    }

    /** Check if the user can manage other users. */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }
}
