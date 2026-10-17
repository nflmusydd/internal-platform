<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Naming Convention
    |--------------------------------------------------------------------------
    |
    | Permissions are NOT the route name verbatim anymore. They are derived
    | from the route name with a naming convention (see App\Support\RoutePermission):
    |
    |   route name  ->  permission
    |   -----------     ----------
    |   m.index     ->  m.view
    |   m.show      ->  m.view
    |   m.create    ->  m.create
    |   m.store     ->  m.create
    |   m.edit      ->  m.edit
    |   m.update    ->  m.edit
    |   m.destroy   ->  m.delete
    |   m.export_x  ->  m.export
    |   m.sync_x    ->  m.edit
    |
    | The action is the LAST non-ignored segment; the module is everything
    | before it. Several routes therefore share one permission (e.g. the page
    | `sysadmin.users.index` and the ajax list `sysadmin.users.ajax.users`
    | both map to `sysadmin.users.view`).
    |
    */

    /*
     * Route names that must NEVER be turned into a permission and are always
     * reachable by any authenticated user. Keep this list as small as possible:
     * every other named route (including ajax endpoints) is guarded.
     */
    'exclude' => [
        'login',
        'register',
        'logout',
        'locale',
    ],

    /*
     * Guard used for the generated permissions.
     */
    'guard' => 'web',

    /*
     * Segments removed from the route name before deriving the permission.
     * These are URL/structure containers, not part of the module identity.
     */
    'ignore_segments' => [
        'ajax',
    ],

    /*
     * Exact action segment -> permission suffix.
     */
    'action_map' => [
        'index' => 'view',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
    ],

    /*
     * Action segment prefix -> permission suffix (checked with str_starts_with).
     */
    'action_prefix_map' => [
        'export' => 'export',
        'sync' => 'edit',
    ],

    /*
     * Read-only action segments (list/detail ajax endpoints) that all resolve
     * to the module `view` permission. Kept explicit so the convention test can
     * fail when a new route is not consciously classified.
     */
    'read_actions' => [
        'all',
        'users',
        'roles',
        'role_permissions',
        'role_users',
        'user_roles',
        'user_permissions',
        'roles_for_user',
        'permissions_for_user',
        'permission_roles',
    ],

    /*
     * Fallback suffix when an action matches none of the above. This is a
     * runtime safety net; the convention test guarantees every real route is
     * classified so it is never relied upon.
     */
    'default_action' => 'view',

];
