# Route Permissions (Internal Platform)

This document explains how access control works in the application. The core rule is:

> **A permission is derived from a route name by a naming convention (module + action).**

A route named `sysadmin.users.index` maps to the permission `sysadmin.users.view`; the
`sysadmin.users.store` and `sysadmin.users.ajax.store` routes both map to
`sysadmin.users.create`. The middleware blocks a route when the authenticated user does not
hold the derived permission, and the sidebar only renders the menus the user is allowed to
open. For menu catalog/sync mechanics see [`menu-management.md`](./menu-management.md); for
the page component conventions see [`iplat1-components.md`](./iplat1-components.md).

## 1. Principle

- **Permissions are action-oriented, not endpoint-oriented.** Several routes (a page and its
  AJAX endpoints) share one permission, e.g. `sysadmin.users.view` covers the page `index`,
  `show` and every read AJAX endpoint of the module.
- **Code-driven.** The set of permissions is derived from the route table by the naming
  convention. Adding a route automatically makes its permission available after the seeder
  runs.
- **Single source of truth.** All of it lives in `App\Support\RoutePermission`.
- **Fail-closed, and recovery is one command.** A named route whose permission is missing is
  *denied* for everyone (except Super Admin), so an accidental deletion can never silently
  expose a module. `php artisan permission:sync` recreates the missing rows (section 9), and an
  automated coverage test guarantees every real application route has a permission.

## 2. Architecture

| Concern | Location |
|---|---|
| Allowlist + guard config | `config/route_permissions.php` |
| Route ↔ permission source of truth | `app/Support/RoutePermission.php` |
| Route guard middleware | `app/Http/Middleware/CheckRoutePermission.php` |
| Middleware alias | `bootstrap/app.php` → `check.route.permission` |
| Middleware application | `routes/web.php` (auth group) |
| Super Admin bypass | `app/Providers/AppServiceProvider.php` → `Gate::before` |
| Sidebar filtering | `app/Providers/AppServiceProvider.php` (view composer) |
| Permission seeding | `database/seeders/RolePermissionSeeder.php` |
| Default roles / bundles / demo users | `app/Support/DefaultRolePermissions.php` |
| Permission recovery command | `app/Console/Commands/PermissionSyncCommand.php` (`permission:sync`) |
| Tests | `tests/Feature/RouteAccessTest.php`, `tests/Feature/RoutePermissionCoverageTest.php` |

`RoutePermission` is a plain static helper so the seeder, the middleware, the sidebar
composer and the tests all share the exact same logic.

## 3. Configuration

`config/route_permissions.php`:

```php
'exclude' => ['login', 'register', 'logout', 'locale'],
'guard'   => 'web',

'ignore_segments'    => ['ajax'],
'action_map'         => [
    'index' => 'view',   'show'   => 'view',
    'create' => 'create', 'store'  => 'create',
    'edit' => 'edit',    'update' => 'edit',
    'destroy' => 'delete',
],
'action_prefix_map'  => ['export' => 'export', 'sync' => 'edit'],
'read_actions'       => ['all', 'users', 'roles', 'role_permissions', 'role_users',
    'user_roles', 'user_permissions', 'roles_for_user', 'permissions_for_user', 'permission_roles'],
'default_action'     => 'view',
```

- **`exclude`** — route names that must never become a permission and stay reachable by any
  authenticated user. Keep it as small as possible; every other named route (including AJAX
  endpoints) is guarded.
- **`guard`** — guard used for the generated permissions (Spatie).
- **`ignore_segments`** — route segments removed before deriving the permission. `ajax` is only
  a URL container (`/ajax/...`), so it is not part of the module identity.
- **`action_map`** / **`action_prefix_map`** — action → permission suffix mapping (section 4).
- **`read_actions`** — read-only action segments (list/detail AJAX endpoints) that all resolve
  to the module `view` permission. Kept explicit so the convention test can fail when a new
  route is not consciously classified.
- **`default_action`** — fallback suffix when nothing matches. It is only a runtime safety net;
  the convention test guarantees every real route is classified.

## 4. How it works

### 4.1 The naming convention

`RoutePermission::permissionFor($routeName)` derives the permission:

1. split the route name on `.`;
2. drop the `ignore_segments` (`ajax`);
3. the **last** remaining segment is the *action*, everything before it is the *module*;
4. translate the action: `action_map` → `action_prefix_map` → `read_actions` (`view`) →
   `default_action`;
5. the permission is `module.suffix`.

| Route name | Permission |
|---|---|
| `sysadmin.users.index` | `sysadmin.users.view` |
| `sysadmin.users.show` | `sysadmin.users.view` |
| `sysadmin.users.create` | `sysadmin.users.create` |
| `sysadmin.users.store` | `sysadmin.users.create` |
| `sysadmin.users.ajax.users` (read) | `sysadmin.users.view` |
| `sysadmin.users.ajax.store` | `sysadmin.users.create` |
| `sysadmin.users.ajax.update` | `sysadmin.users.edit` |
| `sysadmin.users.ajax.destroy` | `sysadmin.users.delete` |
| `sysadmin.users.ajax.export_users` | `sysadmin.users.export` |
| `sysadmin.role_permissions.ajax.sync_permissions` | `sysadmin.role_permissions.edit` |
| `reports.index` | `reports.view` |

Because the page and its AJAX endpoints collapse into one permission, the current project
yields **30** permissions (down from 61 endpoints).

### 4.2 Which routes become permissions

`RoutePermission::routeNames()` walks `Route::getRoutes()` and returns every named route
except:

- the configured **exclude** list, and
- **vendor/framework routes** (see section 6).

`RoutePermission::permissions()` maps that list through the convention and returns the distinct,
sorted permission names. On this project it currently yields **30** permissions.

### 4.3 Creating the permissions

`RoutePermission::sync()` inserts a permission (on the `web` guard) for every name returned by
`permissions()` that does not exist yet, and returns `['created' => [...], 'existing' => [...]]`.
It only ever **creates**; it never deletes a permission. `RolePermissionSeeder` calls it, and
the `permission:sync` command wraps it for recovery (see section 9).

Because the sync **only creates**, deleting a permission row is a mistake that the application
no longer silently tolerates: the middleware fails **closed** (section 5), so the route stays
blocked until the row is recreated with `php artisan permission:sync`.

### 4.4 Guarding a route

Every route inside the authenticated group carries the `check.route.permission` middleware
(`routes/web.php`):

```php
Route::middleware(['auth', 'check.route.permission'])->group(function () {
    // ...
});
```

`CheckRoutePermission` resolves the current route name and calls
`RoutePermission::allows($user, $name)`. When it returns `false`, the request is aborted with
**403** (`general.unauthorized`, EN/ID).

### 4.5 The `allows()` decision

`RoutePermission::allows(?Authenticatable $user, ?string $routeName)`:

| Condition | Result |
|---|---|
| `$routeName` is `null`/empty (folder/placeholder) | allowed |
| `$routeName` is on the **exclude** list | allowed |
| No authenticated user | denied |
| Otherwise | `$user->can(permissionFor($routeName))` (Spatie: traverses roles) |

The check is **fail-closed**: there is no "permission does not exist" short-circuit. If the
permission row for a route is missing, `$user->can()` returns `false` (Spatie swallows
`PermissionDoesNotExist`), so the route is **denied** and the menu disappears. The one
exception is **SUPER ADMIN**: the `Gate::before` bypass lives *inside* `$user->can()`, so a
Super Admin keeps passing even while the permission row is absent. In short: *delete the
permission ⇒ 403 for everyone except Super Admin*.

### 4.6 Super Admin bypass

`AppServiceProvider::boot()` registers a `Gate::before` hook:

```php
Gate::before(fn ($user, $ability) =>
    method_exists($user, 'hasRole') && $user->hasRole('SUPER ADMIN') ? true : null);
```

A **SUPER ADMIN** passes every check without needing each permission assigned individually;
returning `null` for everyone else lets Spatie's own `Gate::before` handler run.

### 4.7 Sidebar filtering

The same view composer that loads the menus filters them with `RoutePermission::allows()`:

- a **leaf** menu is shown only if the user may access its `route_name`;
- a **folder** menu is shown only if at least one of its children is shown.

Note: the legacy `menus.permission_name` column has been **removed**. Filtering is driven purely
by `route_name`, which is mapped to its permission by the convention.

Because filtering uses the same `allows()` as the middleware, deleting a permission hides its
menus and blocks its URLs together — the two can never drift apart.

## 5. Fail-closed + coverage test

Fail-closed means a missing permission row **denies** access instead of opening the route. This
is deliberate: an accidental deletion (or a stale DB snapshot) must never silently expose a
module. Recovery is one command — see section 9.

The trade-off is that the DB must actually be seeded; otherwise every guarded route returns
**403** for non-Super-Admins. `tests/Feature/RoutePermissionCoverageTest.php` fails if any
route returned by `RoutePermission::routeNames()` does not have a matching permission, or if an
allowlisted or vendor route leaks into `routeNames()`. It also fails when a route action is not
explicitly classified by the convention (see sections 3 and 4.1), so a new write endpoint can
never be silently mapped to `view`.

`tests/Feature/RouteAccessTest.php` exercises the end-to-end behaviour:

- Super Admin can open every module page.
- A role restricted to one module gets **403** on other modules.
- A manually typed URL for a menu that is hidden is still blocked server-side.
- The sidebar only shows accessible menus.
- An allowlisted route stays reachable.
- A user without permissions sees no module menus.
- Deleting a route's permission closes the route **and** hides its menu (fail-closed).
- `permission:sync` recreates a deleted permission.
- A route-mapped permission cannot be deleted or renamed through the endpoint (**422**).

## 6. Vendor routes

`Route::getRoutes()` also contains framework routes such as `storage.local` and
`storage.local.upload`. These must **never** become permissions (guarding them would break
file serving). `RoutePermission::isVendorRoute()` detects a route whose action is defined
under `/vendor/` (via reflection of the controller method / closure file path), and
`routeNames()` excludes them.

There is **no automatic deletion** of stale permissions anywhere in the codebase. The sync
never deletes. If a leftover vendor permission (e.g. `storage.local`) ever appears, it is
harmless — it is not derived by the convention, so nothing checks it — and you can remove it
manually. Orphaned *generated* permissions are merely *reported* by `permission:sync`
(section 9), never deleted.

### 6.1 Generated vs manual permissions

Every row in the `permissions` table carries a boolean `is_generated`:

- **Generated (`is_generated = true`)** — created by the convention from a route. These are
  owned by the code: `permission:sync` creates them, self-heals the flag on pre-existing rows,
  and reports them as orphans when their route disappears. They are protected from
  deletion/rename in the UI (section 9.1).
- **Manual (`is_generated = false`)** — created by an admin through **Role & Permissions →
  Permission → Add** for a permission that no route maps to (e.g. a gate used only in
  controllers or Blade). `permission:sync` leaves them untouched and never reports them as
  orphans, because their existence is intentional.

You can filter the Permission page by **Type** (Auto / Manual) and the XLSX export includes the
same column, so generated rows are easy to tell apart from hand-made ones. When you add a
manual permission, remember it is not tied to any route — assign it to roles/users and check it
explicitly (e.g. via `$user->can(...)` or a gate).

## 7. Roles and bundles

`DefaultRolePermissions` (used by both `RolePermissionSeeder` and `permission:sync
--restore-roles`) creates the base roles and assigns permissions by prefix:

| Role | Permissions |
|---|---|
| `SUPER ADMIN` | **all** web permissions (`*`) |
| `DEVELOPER` | `development.components.%`, `development.command.%` |
| `MANAGER` | `work_calendar.%`, `reports.%` |
| `TEAM LEADER` | `work_calendar.%`, `reports.%` |
| `EMPLOYEE` | `work_calendar.%` |

Demo users created by the seeder (password `test1234`): `otoriterman@internal.com`
(SUPER ADMIN), `developer@internal.com` (DEVELOPER), `manager@internal.com` (MANAGER),
`teamleader@internal.com` (TEAM LEADER), `employee@internal.com` (EMPLOYEE).

The bundle prefixes are the realistic starting point; the app UI (Role & Permissions / User
Permissions) is still the place to grant finer access at runtime.

## 8. Both-state permissions (direct + via role)

The User Permission module lets a permission be granted **directly** to a user *and* through
a role at the same time. In the assignment modal such a row stays visually checked and cannot
be unchecked (it is already granted via the role). On the server,
`UserPermissionController::syncPermissions()` **pins** these rows: a both-state permission
that is not present in the submitted payload is preserved instead of being detached, so the
direct grant and its `assigned_by`/`assigned_at` metadata are never silently dropped. A
`422 permission_in_role` is still returned when a *via-role-only* permission is forced in.

## 9. Recovery — `permission:sync`

Because the check is fail-closed, a deleted permission must be recreated. The dedicated
command is the supported fix:

```bash
docker exec dev_internal_platform-app-1 php artisan permission:sync
```

It:

1. creates every permission returned by `permissions()` that is missing (same logic as the
   seeder),
2. prints the created names,
3. flags every existing route-derived permission as generated (`is_generated = true`) — this
   self-heals a route permission that was created manually or before the flag existed,
4. **reports** (does not delete) any *generated* permission that is no longer derived by the
   convention ("orphans"). Manual permissions are intentional and are **not** reported.

To also re-apply the default role bundles (undo manual role edits), use:

```bash
docker exec dev_internal_platform-app-1 php artisan permission:sync --restore-roles
```

There is intentionally **no `--prune`** and no code path that deletes a permission.

### 9.1 Deleting/renaming is blocked in the UI

Deleting a permission also removes it from every role (Spatie detaches it), which is exactly
the mistake that would silently lock a module down. To prevent it, `PermissionController`
refuses to delete or rename any permission whose name is derived from a live route
(`in_array($name, RoutePermission::permissions())`):

- `DELETE /sysadmin/permissions/ajax/{id}` and `PUT /sysadmin/permissions/ajax/{id}` return
  **422** with `permission_locked_route`;
- the message is surfaced by the shared AJAX error toast;
- renaming to a *different* name is blocked; updating only `guard_name` (unchanged name) is
  allowed.

### 9.2 Running the seeder

Docker (always inside the `app` container, matching the project convention):

```bash
docker exec dev_internal_platform-app-1 php artisan db:seed --class=RolePermissionSeeder
```

After editing routes, re-run it to create the new permissions. For a full reset:

```bash
docker exec dev_internal_platform-app-1 php artisan migrate:fresh --seed
```

## 10. Adding a new module / route — checklist

- [ ] Define the route with a **name** following the convention
      (`module.section.action`, e.g. `module.section.ajax.store`). The name is mapped to its
      permission (section 4.1); use a read action (`index`, `show`, or a `read_actions`
      segment) for reads and a write action (`store`, `update`, `destroy`, `export`, `sync`)
      for writes.
- [ ] Do **not** add it to `config/route_permissions.exclude`.
- [ ] If the route introduces a brand-new action segment, classify it in
      `config/route_permissions.php`; otherwise `RoutePermissionCoverageTest` fails.
- [ ] Run `RolePermissionSeeder` (or `php artisan permission:sync`) so the permission row is
      created.
- [ ] Assign the new permission to the relevant role (via the app or by extending the bundles
      in `DefaultRolePermissions`).
- [ ] Add a menu entry in `MenuSynchronizer` with the matching `route_name` and run
      `php artisan menu:sync`.
- [ ] Run the tests — `RoutePermissionCoverageTest` proves the route is covered.

## 11. Tests

```bash
docker exec dev_internal_platform-app-1 php artisan test tests/Feature/RouteAccessTest.php
docker exec dev_internal_platform-app-1 php artisan test tests/Feature/RoutePermissionCoverageTest.php
```
