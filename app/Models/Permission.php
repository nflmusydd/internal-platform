<?php

namespace App\Models;

use App\Traits\HasAutoUlid;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUlids, HasAutoUlid;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }
}
