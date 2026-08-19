<?php

namespace App\Models;

use App\Traits\HasAutoUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Spatie\Permission\Models\Permission as SpatiePermission;

#[Hidden(['id'])]
class Permission extends SpatiePermission
{
    use HasAutoUlid;
}
