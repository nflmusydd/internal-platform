<?php

namespace App\Exports;

use App\Models\Role;

class RolesExport extends BaseExport
{
    public function __construct($roles)
    {
        $title = ucfirst(__('general.roles'));

        $headers = [
            ucwords(__('general.number')),
            ucwords(__('general.name')),
            ucwords(__('general.guard')),
            ucwords(__('general.permissions')),
            ucwords(__('general.used_by')),
            ucwords(__('sysadmin/role-permissions/index.created_at')),
        ];

        $rows = $roles->map(function ($role, $index) {
            return [
                $index + 1,
                $role->name,
                $role->guard_name,
                $role->permissions_count,
                $role->users_count,
                $role->created_at ? $role->created_at->format('d/m/Y H:i') : '-',
            ];
        })->toArray();

        parent::__construct($rows, $title, $headers);
    }
}
