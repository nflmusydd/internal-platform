<?php

namespace App\Models;

use App\Traits\HasAutoUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Spatie\Permission\Models\Role as SpatieRole;

#[Hidden(['id'])]
class Role extends SpatieRole
{
    use HasAutoUlid;
}
