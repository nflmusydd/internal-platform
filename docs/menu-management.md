# Menu Management (Internal Platform)

This document explains how the application sidebar menu is managed: the master catalog,
the `menu:sync` command, and how to add or change a menu. It intentionally focuses on the
sync mechanism and the catalog. For the page/UI component conventions, see
[`iplat1-components.md`](./iplat1-components.md).

## 1. Principle

Menu data is **code-driven, not UI-driven**:

- The single source of truth is the catalog array in
  `App\Support\MenuSynchronizer::catalog()`.
- The database `menus` table is only a projection of that catalog.
- `php artisan menu:sync` is the **only** entrypoint that writes menu data.
- The admin page at `sysadmin/menus` is **read-only** (no create/update/delete).

This avoids the desync problem that would occur if menus could be edited from the UI while
also being managed by a seeder: a re-sync would silently revert user edits.

## 2. Architecture

| Concern | Location |
|---|---|
| Master catalog + sync logic + validation | `app/Support/MenuSynchronizer.php` |
| Artisan command (`menu:sync`) | `app/Console/Commands/MenuSyncCommand.php` |
| Model | `app/Models/Menu.php` |
| Table | `menus` (migration `..._create_menus_table.php`) |
| Admin endpoints | `app/Http/Controllers/Sysadmin/MenuController.php` |
| Export | `app/Exports/MenusExport.php` |
| Admin view | `resources/views/sysadmin/menus/index.blade.php` |
| Routes | `routes/web.php` → `sysadmin.menus.*` |
| Sidebar composer | `app/Providers/AppServiceProvider.php` |
| Sidebar view | `resources/views/layouts/iplat1_layout1.blade.php` |

`MenuSynchronizer` is a plain service so the logic stays testable and could be reused
(e.g. by a future command), while the command is just a thin presentation layer.

## 3. `menus` table

| Column | Notes |
|---|---|
| `id` | auto-increment PK (internal, hidden from JSON via `#[Hidden(['id'])]`) |
| `ulid` | public identifier |
| `slug` | unique, stable key used by the catalog |
| `parent_id` | nullable self-FK (`menus.id`, `onDelete: cascade`) |
| `name_en`, `name_id` | bilingual labels |
| `route_name` | Laravel named route; `null` for a folder/parent |
| `icon` | blade view path, e.g. `resources/views/layouts/menu_icons/sysadmin.blade.php` |
| `order` | sort order |
| `is_active` | boolean |
| `permission_name` | Spatie permission name (reserved for permission-based filtering) |
| `created_at`, `created_by`, `updated_at`, `updated_by` | audit columns |

Constraints:

- `slug` is unique.
- `unique(parent_id, order)` — `order` must be unique **among siblings** (same parent).
- Deleting a parent cascades to its children.

## 4. How sync works

`MenuSynchronizer::sync()` compares the catalog against the DB and returns a result array:

```php
[
    'inserted' => [...slugs],
    'updated'  => [...slugs],
    'skipped'  => [...slugs],
    'deleted'  => [...slugs],
]
```

Behaviour per catalog entry:

- **Insert** — slug not in DB yet.
- **Update** — slug exists but any compared field differs.
- **Skip** — slug exists and nothing changed.
- **Delete** — any DB row whose slug is **not** in the catalog is deleted.

Compared fields: `parent_id`, `name_en`, `name_id`, `route_name`, `icon`, `order`,
`is_active`, `permission_name`.

The whole operation runs in a transaction; any failure rolls back and logs to
`Log::error('MENU SYNC: Process Failed')`.

### 4.1 Catalog validation

Before touching the DB, `validateCatalog()` enforces:

1. **`parent_slug` must exist in the catalog.** A typo would otherwise silently create a
   root menu.
2. **A menu that has children must not define `route_name`.** Such a menu is a
   folder/dropdown; the sidebar ignores `route_name` on parents anyway, so this keeps the
   data and the rendering consistent.
3. **`order` must be an integer and unique among siblings.** Sibling scope is the
   `parent_slug` (`root` for top-level menus), so two different parents may both use
   `order = 1`. A duplicate throws a message naming both slugs.
4. **The parent chain must not contain a cycle.**

Invalid catalogs throw and the sync does not run.

### 4.2 Example output

```text
=========== MENU SYNC RESULT ===========
Inserted (1):
sysadmin/reports
Updated (0):

Skipped (8):
sysadmin, sysadmin/menus, sysadmin/users, ...
Deleted (0):

```

### 4.3 New parents and reordering

Two behaviours are worth knowing:

- **New parents are processed before their children.** The catalog is walked parent-first,
  and the `slug → id` map is updated as rows are inserted. A brand-new parent plus its
  children can therefore be added in a single run, even on an empty database.
- **Reordering is safe.** Because `order` is unique among siblings, swapping or inserting
  between siblings could momentarily collide with the constraint. Before the final orders
  are written, existing orders in every affected parent scope are shifted out of the
  catalog range, then the final values are applied. As a result, a second consecutive run
  changes nothing (everything is reported as skipped).

## 5. Running the sync

Host:

```bash
php artisan menu:sync
```

Docker (from the project root, compose files live in `docker/`):

```bash
# (container_name: app)
docker exec app php artisan menu:sync
```

> Running `php artisan menu:sync` from the host against the Docker database fails with
> `getaddrinfo for mysql failed`, because the host `mysql` name only resolves inside the
> Docker network. Always run it inside the `app` container.

## 6. Adding or changing a menu

1. **Edit the catalog** in `App\Support\MenuSynchronizer::catalog()` and add/modify an entry:

   ```php
   [
       'slug' => 'sysadmin/reports',        // unique, stable key
       'name_en' => 'Reports',
       'name_id' => 'Laporan',
       'route_name' => 'sysadmin.reports.index', // null if it is a folder
       'icon' => 'resources/views/layouts/menu_icons/sysadmin_reports.blade.php',
       'parent_slug' => 'sysadmin',         // null for a top-level menu
       'order' => 5,                        // unique among siblings
       'is_active' => true,
       'permission_name' => 'view_sysadmin_reports',
   ],
   ```

2. **Make sure `route_name` is a registered route.** The catalog does not validate route
   existence; the sidebar calls `route($menu->route_name)`, so an unknown name errors at
   render time.

3. **(Optional) Add the icon partial** at the path given in `icon`. Each file contains a
   single Bootstrap icon tag, e.g.:

   ```blade
   {{-- resources/views/layouts/menu_icons/sysadmin_reports.blade.php --}}
   <i class="bi bi-bar-chart-fill"></i>
   ```

   `App\Helpers\LayoutHelper::convertToViewPath()` converts the stored path to a view name.
   If the partial is missing, the sidebar falls back to a default icon
   (`bi-square-fill` for parents, `bi-dash-lg` for children).

4. **Run the sync** (see section 5).

5. **To remove a menu**, delete its entry from the catalog (and any children) and run the
   sync; it will be removed from the DB.

### Order notes

- `order` is unique **per parent**, so a parent and a child may both use `order = 1`.
- Keep `order` contiguous to avoid confusion when reordering.
- Reordering or swapping sibling `order` values in the catalog is supported; the sync
  shifts the affected scopes temporarily so the unique constraint is never violated.

## 7. How menus are rendered (brief)

- A view composer in `AppServiceProvider::boot()` loads active top-level menus with their
  active, ordered children and shares them to the layout as `$menus`.
- The sidebar loop in `iplat1_layout1.blade.php` renders children as a dropdown when a
  menu has children, otherwise as a single link.
- The breadcrumb component (`components.iplat1.breadcrumb`) also reads the `menus` table,
  keyed by a group slug.
- Permission-based filtering of the sidebar (Spatie `permission_name`) is currently
  commented out; all active menus are shown.

## 8. Admin page (brief)

`sysadmin/menus` is a read-only, flat-indented DataTable that shows the full hierarchy.
It supports filters (name, parent, status, created-at and updated-at ranges) and XLSX
export, but no create/edit/delete. Rows are flattened server-side by
`MenuController::flattenTree()` (parent then `order`) and the table keeps that order
(client-side ordering disabled).

## 9. Checklist

- [ ] Entry added/edited in `MenuSynchronizer::catalog()`.
- [ ] `slug` unique; `parent_slug` exists in the catalog.
- [ ] Parent menus have `route_name = null`; leaf menus point to a registered route.
- [ ] `order` unique among siblings.
- [ ] Icon partial created (optional).
- [ ] `php artisan menu:sync` run inside the `app` container.
- [ ] Result shows the expected Inserted/Updated/Skipped/Deleted.
