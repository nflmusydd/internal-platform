<?php

namespace App\Models;

use App\Traits\HasAutoUlid;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUlids, HasAutoUlid;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }
}
