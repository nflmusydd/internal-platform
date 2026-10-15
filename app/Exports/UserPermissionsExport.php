<?php

namespace App\Exports;

class UserPermissionsExport extends BaseExport
{
    public function __construct($users)
    {
        $title = ucfirst(__('sysadmin/user-permissions/index.user_permissions'));

        $headers = [
            ucwords(__('general.number')),
            ucwords(__('general.name')),
            ucwords(__('general.email')),
            ucwords(__('sysadmin/user-permissions/index.column_status')),
            ucwords(__('sysadmin/user-permissions/index.column_roles')),
            ucwords(__('sysadmin/user-permissions/index.column_permissions')),
        ];

        $rows = $users->map(function ($user, $index) {
            return [
                $index + 1,
                $user->name,
                $user->email,
                $user->is_active
                    ? ucfirst(__('sysadmin/user-permissions/index.status_active'))
                    : ucfirst(__('sysadmin/user-permissions/index.status_inactive')),
                $user->roles_count,
                $user->permissions_count,
            ];
        })->toArray();

        parent::__construct($rows, $title, $headers);
    }
}
