<?php

namespace App\Exports;

class MenusExport extends BaseExport
{
    public function __construct($menus)
    {
        $locale = app()->getLocale();
        $title = ucfirst(__('sysadmin/menus/index.title'));

        $headers = [
            ucwords(__('general.number')),
            ucwords(__('general.menu')),
            ucwords(__('sysadmin/menus/index.slug_label')),
            ucwords(__('sysadmin/menus/index.parent_label')),
            ucwords(__('sysadmin/menus/index.route_label')),
            ucwords(__('sysadmin/menus/index.permission_label')),
            ucwords(__('sysadmin/menus/index.order_label')),
            ucwords(__('sysadmin/menus/index.status_label')),
            ucwords(__('sysadmin/menus/index.created_at')),
            ucwords(__('sysadmin/menus/index.updated_at')),
        ];

        $rows = collect($menus)->map(function ($menu, $index) use ($locale) {
            $name = $locale === 'id'
                ? ($menu['name_id'] ?: $menu['name_en'])
                : ($menu['name_en'] ?: $menu['name_id']);

            return [
                $index + 1,
                str_repeat('    ', $menu['depth']) . ($menu['depth'] > 0 ? '└ ' : '') . $name,
                $menu['slug'],
                $menu['parent_name'] ?: '-',
                $menu['route_name'] ?? '-',
                $menu['permission_name'] ?? '-',
                $menu['order'],
                $menu['is_active'] ? ucfirst(__('sysadmin/menus/index.active')) : ucfirst(__('sysadmin/menus/index.inactive')),
                $menu['created_at'] ? $menu['created_at']->format('d/m/Y H:i') : '-',
                $menu['updated_at'] ? $menu['updated_at']->format('d/m/Y H:i') : '-',
            ];
        })->toArray();

        parent::__construct($rows, $title, $headers);
    }
}
