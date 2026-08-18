<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasAutoUlid
{
    public static function bootHasAutoUlid(): void
    {
        static::creating(function ($model) {
            if (empty($model->ulid) && in_array('ulid', $model->getFillable())) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }
}
