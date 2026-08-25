<?php

namespace App\Exports;

class UsersExport extends BaseExport
{
    public function __construct($users)
    {
        $title = ucfirst(__('general.users'));

        $headers = [
            ucwords(__('general.number')),
            ucwords(__('general.name')),
            ucwords(__('general.email')),
            ucwords(__('sysadmin/users/index.status_label')),
            ucwords(__('sysadmin/users/index.created_at')),
            ucwords(__('sysadmin/users/index.updated_at')),
        ];

        $rows = $users->map(function ($user, $index) {
            return [
                $index + 1,
                $user->name,
                $user->email,
                $user->is_active ? ucfirst(__('sysadmin/users/index.active')) : ucfirst(__('sysadmin/users/index.inactive')),
                $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-',
                $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : '-',
            ];
        })->toArray();

        parent::__construct($rows, $title, $headers);
    }
}
