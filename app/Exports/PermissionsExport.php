<?php

namespace App\Exports;

use App\Models\Permission;

class PermissionsExport extends BaseExport
{
    public function __construct($permissions)
    {
        $title = ucfirst(__('general.permissions'));

        $headers = [
            ucwords(__('general.number')),
            ucwords(__('general.name')),
            ucwords(__('general.guard')),
            ucwords(__('general.used_by')),
            ucwords(__('sysadmin/role-permissions/index.created_at')),
        ];

        $rows = $permissions->map(function ($perm, $index) {
            return [
                $index + 1,
                $perm->name,
                $perm->guard_name,
                $perm->roles_count,
                $perm->created_at ? $perm->created_at->format('d/m/Y H:i') : '-',
            ];
        })->toArray();

        parent::__construct($rows, $title, $headers);
    }
}
