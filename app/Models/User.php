<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Pivots\ModelHasPermission;
use App\Models\Pivots\ModelHasRole;
use App\Traits\HasAutoUlid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active', 'current_photo', 'theme', 'created_by', 'updated_by'])]
#[Hidden(['id', 'password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasAutoUlid, HasFactory, HasRoles, Notifiable {
        roles as protected spatieRoles;
        permissions as protected spatiePermissions;
    }

    /**
     * Roles relation using our pivot model so every assignment records
     * `assigned_by` / `assigned_at` (see App\Models\Pivots\ModelHasRole).
     */
    public function roles(): BelongsToMany
    {
        return $this->spatieRoles()
            ->using(ModelHasRole::class)
            ->withPivot(['assigned_by', 'assigned_at']);
    }

    /**
     * Direct permissions relation using our pivot model so every assignment
     * records `assigned_by` / `assigned_at` (see App\Models\Pivots\ModelHasPermission).
     */
    public function permissions(): BelongsToMany
    {
        return $this->spatiePermissions()
            ->using(ModelHasPermission::class)
            ->withPivot(['assigned_by', 'assigned_at']);
    }

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
}
