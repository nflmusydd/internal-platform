<?php

namespace App\Support;

use App\Models\Permission;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;

/**
 * Route name -> permission name by naming convention.
 *
 * Single source of truth for:
 * - which route names become permissions (every named application route except
 *   the configured allowlist and framework/vendor routes),
 * - mapping a route name to its permission name (action-oriented, shared across
 *   the page and the ajax endpoints of the same module),
 * - creating the missing permissions from the route table,
 * - checking whether a user may access a route (or see its menu).
 *
 * Kept framework-only so it can be reused by the seeder, the route middleware,
 * the sidebar view composer and the tests without duplication.
 */
class RoutePermission
{
    /**
     * Route names that are never turned into a permission and stay reachable by
     * any authenticated user.
     *
     * @return array<int, string>
     */
    public static function excludedNames(): array
    {
        return array_values((array) config('route_permissions.exclude', []));
    }

    /**
     * Guard used for the generated permissions.
     */
    public static function guard(): string
    {
        return (string) config('route_permissions.guard', 'web');
    }

    /**
     * Segments removed from the route name before deriving the permission.
     *
     * @return array<int, string>
     */
    public static function ignoreSegments(): array
    {
        return array_values((array) config('route_permissions.ignore_segments', []));
    }

    /**
     * Exact action segment -> permission suffix.
     *
     * @return array<string, string>
     */
    public static function actionMap(): array
    {
        return (array) config('route_permissions.action_map', []);
    }

    /**
     * Action segment prefix -> permission suffix (matched with str_starts_with).
     *
     * @return array<string, string>
     */
    public static function actionPrefixMap(): array
    {
        return (array) config('route_permissions.action_prefix_map', []);
    }

    /**
     * Read-only action segments that resolve to the module `view` permission.
     *
     * @return array<int, string>
     */
    public static function readActions(): array
    {
        return array_values((array) config('route_permissions.read_actions', []));
    }

    /**
     * Fallback suffix when an action matches none of the other rules.
     */
    public static function defaultAction(): string
    {
        return (string) config('route_permissions.default_action', 'view');
    }

    /**
     * Every named application route that should have a matching permission
     * (sorted, unique). Allowlisted and framework/vendor routes are ignored.
     *
     * @return array<int, string>
     */
    public static function routeNames(): array
    {
        $excluded = static::excludedNames();
        $names = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || $name === '' || in_array($name, $excluded, true)) {
                continue;
            }

            // Framework/vendor routes (e.g. `storage.local`) must never become
            // permissions: guarding file serving would break the application.
            if (static::isVendorRoute($route)) {
                continue;
            }

            $names[$name] = true;
        }

        $names = array_keys($names);
        sort($names);

        return $names;
    }

    /**
     * Map a route name to its permission name by convention.
     *
     * - segments in `ignore_segments` are dropped,
     * - the last remaining segment is the action, the rest is the module,
     * - the action is translated through the action maps,
     * - the permission is `module.suffix`.
     *
     * Examples:
     *   sysadmin.users.index             -> sysadmin.users.view
     *   sysadmin.users.store             -> sysadmin.users.create
     *   sysadmin.users.ajax.users        -> sysadmin.users.view
     *   sysadmin.users.ajax.export_users -> sysadmin.users.export
     */
    public static function permissionFor(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        $ignore = static::ignoreSegments();
        $segments = array_values(array_filter(
            explode('.', $routeName),
            fn (string $segment) => ! in_array($segment, $ignore, true)
        ));

        if ($segments === []) {
            return null;
        }

        $action = (string) array_pop($segments);
        $module = implode('.', $segments);
        $suffix = static::mapAction($action);

        return $module === '' ? $suffix : $module . '.' . $suffix;
    }

    /**
     * Resolve a route action segment to a permission suffix.
     */
    public static function mapAction(string $action): string
    {
        $map = static::actionMap();

        if (isset($map[$action])) {
            return $map[$action];
        }

        foreach (static::actionPrefixMap() as $prefix => $suffix) {
            if (str_starts_with($action, (string) $prefix)) {
                return (string) $suffix;
            }
        }

        if (in_array($action, static::readActions(), true)) {
            return 'view';
        }

        return static::defaultAction();
    }

    /**
     * Whether a route action segment is explicitly classified (used by the
     * convention test so new routes cannot silently fall through to the default).
     */
    public static function isActionClassified(string $action): bool
    {
        if (array_key_exists($action, static::actionMap())) {
            return true;
        }

        foreach (array_keys(static::actionPrefixMap()) as $prefix) {
            if (str_starts_with($action, (string) $prefix)) {
                return true;
            }
        }

        return in_array($action, static::readActions(), true);
    }

    /**
     * Distinct permission names derived from all application routes (sorted).
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        $permissions = [];

        foreach (static::routeNames() as $routeName) {
            $permission = static::permissionFor($routeName);

            if ($permission !== null) {
                $permissions[$permission] = true;
            }
        }

        $permissions = array_keys($permissions);
        sort($permissions);

        return $permissions;
    }

    /**
     * Whether the route's action is defined inside the vendor directory.
     */
    protected static function isVendorRoute(RouteInstance $route): bool
    {
        $uses = $route->getAction()['uses'] ?? null;
        $file = null;

        if ($uses instanceof \Closure) {
            $file = (new \ReflectionFunction($uses))->getFileName();
        } elseif (is_string($uses) && $uses !== '') {
            [$class, $method] = array_pad(explode('@', $uses, 2), 2, null);

            if ($class !== null && class_exists($class)) {
                $file = ($method !== null && method_exists($class, $method))
                    ? (new \ReflectionMethod($class, $method))->getFileName()
                    : (new \ReflectionClass($class))->getFileName();
            }
        }

        if (! $file) {
            return false;
        }

        return str_contains(str_replace('\\', '/', $file), '/vendor/');
    }

    /**
     * Whether the user may access the given route (or see its menu entry).
     *
     * Fail-closed:
     * - No route name (folder / placeholder) -> allowed.
     * - Route on the allowlist                -> allowed.
     * - No user                               -> denied.
     * - Otherwise the user must hold the permission derived from the route name.
     *   A deleted/missing permission therefore denies access (except Super Admin,
     *   who bypasses every check through the Gate::before hook).
     */
    public static function allows(?Authenticatable $user, ?string $routeName): bool
    {
        if ($routeName === null || $routeName === '') {
            return true;
        }

        if (in_array($routeName, static::excludedNames(), true)) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        $permission = static::permissionFor($routeName);

        if ($permission === null) {
            return true;
        }

        return $user->can($permission);
    }

    /**
     * Create a permission for every derived permission name that does not exist
     * yet, and make sure every existing route-derived permission is flagged as
     * generated. It never deletes a row.
     *
     * @return array{created: array<int, string>, existing: array<int, string>, marked: array<int, string>}
     */
    public static function sync(): array
    {
        $guard = static::guard();
        $permissions = static::permissions();
        $existing = Permission::where('guard_name', $guard)->pluck('name')->all();
        $existingMap = array_flip($existing);

        $created = [];
        $found = [];

        foreach ($permissions as $name) {
            if (isset($existingMap[$name])) {
                $found[] = $name;

                continue;
            }

            Permission::create([
                'name' => $name,
                'guard_name' => $guard,
                'is_generated' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $created[] = $name;
        }

        // Self-heal: a route-derived permission may have been created manually
        // (or by an older version) and must still be flagged as generated.
        $marked = Permission::where('guard_name', $guard)
            ->whereIn('name', $permissions)
            ->where('is_generated', false)
            ->pluck('name')
            ->all();

        if ($marked !== []) {
            Permission::where('guard_name', $guard)
                ->whereIn('name', $marked)
                ->update(['is_generated' => true]);
        }

        return ['created' => $created, 'existing' => $found, 'marked' => $marked];
    }
}
