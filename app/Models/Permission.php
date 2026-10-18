<?php

namespace App\Models;

use App\Traits\HasAutoUlid;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\PermissionRegistrar;

#[Hidden(['id'])]
class Permission extends SpatiePermission
{
    use HasAutoUlid;

    protected $casts = [
        'is_generated' => 'boolean',
    ];

    /**
     * A permission belongs to some users of the model associated with its guard.
     * Override to be robust when guard has no provider (prevents morphedByMany(null)).
     */
    public function users(): BelongsToMany
    {
        $guard = $this->guard_name ?? config('auth.defaults.guard');
        $model = Guard::getModelForGuard($guard);

        if ($model === null) {
            // Guard has no provider model: return an empty, safe relation.
            return $this->morphedByMany(
                User::class,
                'model',
                config('permission.table_names.model_has_permissions'),
                app(PermissionRegistrar::class)->pivotPermission,
                config('permission.column_names.model_morph_key')
            )->whereRaw('1 = 0');
        }

        return parent::users();
    }
}
