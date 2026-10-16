<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * Pivot row of the spatie model_has_roles table, carrying assignment tracking.
 */
class ModelHasRole extends MorphPivot
{
    protected $table = 'model_has_roles';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            $pivot->assigned_at ??= now();
            $pivot->assigned_by ??= auth()->id() ?: 0;  // 0=unknown user
        });
    }
}
