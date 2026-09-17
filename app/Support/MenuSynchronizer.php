<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuSynchronizer
{
    /**
     * Master menu catalog. This is the single source of truth for menu structure.
     *
     * Sync behaviour:
     * - Menu yang ada di sini tapi belum ada di DB -> INSERT
     * - Menu yang ada di DB tapi field-nya beda    -> UPDATE
     * - Menu yang ada di DB tapi TIDAK ada di sini -> DELETE
     */
    public static function catalog(): array
    {
        return [
            // ======================== SYSADMIN ========================
            [
                'slug' => 'sysadmin',
                'name_en' => 'Sysadmin',
                'name_id' => 'Sysadmin',
                'route_name' => null,
                'icon' => 'resources/views/layouts/menu_icons/sysadmin.blade.php',
                'parent_slug' => null,
                'order' => 1,
                'is_active' => true,
                'permission_name' => 'view_sysadmin',
            ],
            [
                'slug' => 'sysadmin/menus',
                'name_en' => 'Menu',
                'name_id' => 'Menu',
                'route_name' => 'sysadmin.menus.index',
                'icon' => 'resources/views/layouts/menu_icons/sysadmin_menus.blade.php',
                'parent_slug' => 'sysadmin',
                'order' => 1,
                'is_active' => true,
                'permission_name' => 'view_sysadmin_menus',
            ],
            [
                'slug' => 'sysadmin/users',
                'name_en' => 'User',
                'name_id' => 'Pengguna',
                'route_name' => 'sysadmin.users.index',
                'icon' => 'resources/views/layouts/menu_icons/sysadmin_users.blade.php',
                'parent_slug' => 'sysadmin',
                'order' => 2,
                'is_active' => true,
                'permission_name' => 'view_sysadmin_users',
            ],
            [
                'slug' => 'sysadmin/role-permissions',
                'name_en' => 'Role & Permissions',
                'name_id' => 'Peran & Hak Akses',
                'route_name' => 'sysadmin.role_permissions.index',
                'icon' => 'resources/views/layouts/menu_icons/sysadmin_role_permissions.blade.php',
                'parent_slug' => 'sysadmin',
                'order' => 3,
                'is_active' => true,
                'permission_name' => 'view_sysadmin_role_permissions',
            ],
            [
                'slug' => 'sysadmin/user-permissions',
                'name_en' => 'User Permissions',
                'name_id' => 'Hak Akses Pengguna',
                'route_name' => 'sysadmin.user_permissions.index',
                'icon' => 'resources/views/layouts/menu_icons/sysadmin_user_permissions.blade.php',
                'parent_slug' => 'sysadmin',
                'order' => 4,
                'is_active' => true,
                'permission_name' => 'view_sysadmin_user_permissions',
            ],
            // ======================== DEVELOPMENT ========================
            [
                'slug' => 'development',
                'name_en' => 'Development',
                'name_id' => 'Development',
                'route_name' => null,
                'icon' => 'resources/views/layouts/menu_icons/development.blade.php',
                'parent_slug' => null,
                'order' => 2,
                'is_active' => true,
                'permission_name' => 'view_development',
            ],
            [
                'slug' => 'development/components',
                'name_en' => 'Component',
                'name_id' => 'Komponen',
                'route_name' => 'development.components.index',
                'icon' => 'resources/views/layouts/menu_icons/development_components.blade.php',
                'parent_slug' => 'development',
                'order' => 1,
                'is_active' => true,
                'permission_name' => 'view_development_components',
            ],
            // ======================== WORK CALENDAR ========================
            [
                'slug' => 'work-calendar',
                'name_en' => 'Work Calendar',
                'name_id' => 'Kalender Pekerjaan',
                'route_name' => 'work_calendar.index',
                'icon' => null,
                'parent_slug' => null,
                'order' => 3,
                'is_active' => false,
                'permission_name' => 'view_work_calendar',
            ],
        ];
    }

    /**
     * Validate the master catalog before syncing.
     *
     * - parent_slug must reference an existing catalog slug
     * - a menu that has children must not define a route_name (it is a folder)
     * - order must be an integer, unique among siblings (same parent_slug)
     * - the parent chain must not contain a cycle
     */
    protected static function validateCatalog(array $menus): void
    {
        $catalogSlugs = array_column($menus, 'slug');
        $parentSlugs = array_values(array_filter(array_column($menus, 'parent_slug')));
        $orders = [];

        foreach ($menus as $item) {
            if (! empty($item['parent_slug']) && ! in_array($item['parent_slug'], $catalogSlugs, true)) {
                throw new \Exception(
                    "Menu '{$item['slug']}' references unknown parent_slug '{$item['parent_slug']}'."
                );
            }

            if (in_array($item['slug'], $parentSlugs, true) && ! empty($item['route_name'])) {
                throw new \Exception(
                    "Menu '{$item['slug']}' has children, so route_name must be null."
                );
            }

            if (! is_int($item['order'])) {
                throw new \Exception(
                    "Menu '{$item['slug']}' must define an integer order."
                );
            }

            $scope = $item['parent_slug'] ?: 'root';
            $key = $scope.'|'.$item['order'];

            if (isset($orders[$key])) {
                throw new \Exception(
                    "Menus '{$orders[$key]}' and '{$item['slug']}' share order {$item['order']} under parent '{$scope}'."
                );
            }

            $orders[$key] = $item['slug'];
        }

        $parents = array_column($menus, 'parent_slug', 'slug');

        foreach ($parents as $slug => $parent) {
            $seen = [];
            $cursor = $parent;

            while ($cursor !== null) {
                if ($cursor === $slug) {
                    throw new \Exception("Menu catalog contains a parent cycle at '{$slug}'.");
                }

                if (isset($seen[$cursor])) {
                    break;
                }

                $seen[$cursor] = true;
                $cursor = $parents[$cursor] ?? null;
            }
        }
    }

    /**
     * Order catalog items so a parent is always processed before its children.
     * Stable: items sharing a depth keep their original catalog order.
     */
    protected static function sortByDepth(array $menus): array
    {
        $parents = array_column($menus, 'parent_slug', 'slug');
        $depths = [];

        $depthOf = function (string $slug) use (&$depthOf, &$depths, $parents): int {
            if (array_key_exists($slug, $depths)) {
                return $depths[$slug];
            }

            $parent = $parents[$slug] ?? null;
            $depths[$slug] = $parent ? $depthOf($parent) + 1 : 0;

            return $depths[$slug];
        };

        foreach (array_keys($parents) as $slug) {
            $depthOf($slug);
        }

        $decorated = [];

        foreach ($menus as $index => $item) {
            $decorated[] = [
                'item' => $item,
                'depth' => $depths[$item['slug']],
                'index' => $index,
            ];
        }

        usort($decorated, function ($a, $b) {
            return $a['depth'] <=> $b['depth'] ?: $a['index'] <=> $b['index'];
        });

        return array_column($decorated, 'item');
    }

    /**
     * Move existing orders in the given parent scopes out of the catalog range so
     * final orders can be written without tripping the unique(parent_id, order)
     * constraint (e.g. when swapping two siblings or inserting between them).
     *
     * Temporary value is derived from the row id, which is globally unique, so the
     * shift itself can never collide - even for root rows whose unique(parent_id,
     * order) is not enforced by the database (NULL parent_id is treated as distinct).
     */
    protected static function shiftScopes(array $scopes): void
    {
        foreach (array_keys($scopes) as $scopeKey) {
            $query = DB::table('menus');

            if ($scopeKey === '__root__') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $scopeKey);
            }

            foreach ($query->get() as $row) {
                DB::table('menus')->where('id', $row->id)->update(['order' => 1000000 + $row->id]);
            }
        }
    }

    /**
     * Synchronize the menus table with the master catalog.
     *
     * @return array{inserted: array, updated: array, skipped: array, deleted: array}
     */
    public static function sync(): array
    {
        $menus = static::catalog();

        static::validateCatalog($menus);

        DB::beginTransaction();

        try {
            $insertedSlugs = $updatedSlugs = $skippedSlugs = $deletedSlugs = [];
            $masterSlugs = array_column($menus, 'slug');

            $existingMenus = DB::table('menus')->get()->keyBy('slug');

            // Live slug => id map, kept in sync while inserting so children can resolve
            // parents that were created earlier in the same run.
            $slugToId = $existingMenus
                ->mapWithKeys(fn ($menu) => [$menu->slug => $menu->id])
                ->all();

            $plans = [];
            $affectedScopes = [];

            // Plan first (using the original DB state) so skip/update decisions are accurate.
            foreach (static::sortByDepth($menus) as $item) {
                $existing = $existingMenus[$item['slug']] ?? null;
                $parentSlug = ! empty($item['parent_slug']) ? $item['parent_slug'] : null;

                // A parent that is not in the database yet cannot be referenced by any
                // existing row, so its scope never needs a temporary shift.
                $parentWillBeNew = $parentSlug !== null && ! isset($existingMenus[$parentSlug]);
                $parentId = ($parentSlug === null || $parentWillBeNew)
                    ? null
                    : $existingMenus[$parentSlug]->id;

                $parentChanged = ! $existing
                    || $parentWillBeNew
                    || $existing->parent_id != $parentId;

                $isChanged = $parentChanged
                    || $existing->name_en !== $item['name_en']
                    || $existing->name_id !== $item['name_id']
                    || $existing->route_name !== $item['route_name']
                    || $existing->icon !== $item['icon']
                    || $existing->order != $item['order']
                    || (bool) $existing->is_active !== (bool) $item['is_active']
                    || $existing->permission_name !== $item['permission_name'];

                if ($existing && ! $isChanged) {
                    $action = 'skip';
                } elseif ($existing) {
                    $action = 'update';
                } else {
                    $action = 'insert';
                }

                // Scope is keyed by the parent id known before this run. New parents have
                // no existing rows, so they are not shiftable and need no scope entry.
                $scopeKey = ($parentSlug === null)
                    ? '__root__'
                    : ($parentWillBeNew ? null : (string) $parentId);

                $plans[] = [
                    'item' => $item,
                    'existing' => $existing,
                    'parent_slug' => $parentSlug,
                    'scope_key' => $scopeKey,
                    'action' => $action,
                ];

                // A scope needs a temporary shift whenever an order is introduced into it:
                // an insert, an order change, or a row moved in from another parent.
                if ($scopeKey !== null
                    && (! $existing || $existing->order != $item['order'] || $parentChanged)) {
                    $affectedScopes[$scopeKey] = true;
                }
            }

            static::shiftScopes($affectedScopes);

            foreach ($plans as $plan) {
                $item = $plan['item'];
                $existing = $plan['existing'];
                $action = $plan['action'];
                $parentSlug = $plan['parent_slug'];

                if ($parentSlug === null) {
                    $parentId = null;
                } elseif (array_key_exists($parentSlug, $slugToId)) {
                    $parentId = $slugToId[$parentSlug];
                } else {
                    throw new \Exception(
                        "Menu '{$item['slug']}' references parent_slug '{$parentSlug}' that could not be resolved."
                    );
                }

                $data = [
                    'slug' => $item['slug'],
                    'parent_id' => $parentId,
                    'name_en' => $item['name_en'],
                    'name_id' => $item['name_id'],
                    'route_name' => $item['route_name'],
                    'icon' => $item['icon'],
                    'order' => $item['order'],
                    'is_active' => $item['is_active'],
                    'permission_name' => $item['permission_name'],
                ];

                $validator = Validator::make($data, [
                    'slug' => 'required|string|max:50',
                    'parent_id' => 'nullable|exists:menus,id',
                    'name_en' => 'required|string|max:50',
                    'name_id' => 'required|string|max:50',
                    'route_name' => 'nullable|string|max:255',
                    'icon' => 'nullable|string|max:255',
                    'order' => [
                        'required',
                        'integer',
                        Rule::unique('menus')->where(function ($query) use ($parentId) {
                            return $query->where('parent_id', $parentId);
                        })->when($existing, function ($rule) use ($existing) {
                            return $rule->ignore($existing->id);
                        }),
                    ],
                    'is_active' => 'boolean',
                    'permission_name' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $errors = json_encode($validator->errors()->all());
                    throw new \Exception("Failed to validate menu: '{$item['slug']}': {$errors}");
                }

                if ($action === 'skip') {
                    // The order may have been shifted temporarily; restore it.
                    if (isset($affectedScopes[$plan['scope_key']])) {
                        DB::table('menus')
                            ->where('slug', $item['slug'])
                            ->update(['order' => $item['order']]);
                    }

                    $skippedSlugs[] = $item['slug'];

                    continue;
                }

                if ($existing) {
                    $payload = $data;
                    unset($payload['slug']);

                    DB::table('menus')
                        ->where('slug', $item['slug'])
                        ->update($payload + [
                            'updated_at' => now(),
                            'updated_by' => 1,
                        ]);
                    $updatedSlugs[] = $item['slug'];
                } else {
                    $data['ulid'] = (string) Str::ulid();
                    $data['created_at'] = now();
                    $data['updated_at'] = now();
                    $data['created_by'] = 1;
                    $data['updated_by'] = 1;

                    $slugToId[$item['slug']] = DB::table('menus')->insertGetId($data);
                    $insertedSlugs[] = $item['slug'];
                }
            }

            $deletedSlugs = DB::table('menus')
                ->whereNotIn('slug', $masterSlugs)
                ->pluck('slug')
                ->toArray();

            DB::table('menus')->whereNotIn('slug', $masterSlugs)->delete();

            DB::commit();

            Log::info('MENU SYNC'.': Process committed', [
                'inserted' => ['count' => count($insertedSlugs), 'slugs' => $insertedSlugs],
                'updated' => ['count' => count($updatedSlugs), 'slugs' => $updatedSlugs],
                'skipped' => ['count' => count($skippedSlugs), 'slugs' => $skippedSlugs],
                'deleted' => ['count' => count($deletedSlugs), 'slugs' => $deletedSlugs],
            ]);

            return [
                'inserted' => $insertedSlugs,
                'updated' => $updatedSlugs,
                'skipped' => $skippedSlugs,
                'deleted' => $deletedSlugs,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MENU SYNC'.': Process Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
