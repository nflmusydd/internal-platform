<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuSyncSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // MASTER DATA/SYNC MENU
        // JIKA MENU TIDAK ADA DI $menus, MAKA AKAN DIHAPUS DI DB
        $menus = [
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
            // [
            //     'slug' => 'ulid-test',
            //     'name_en' => 'ulid',
            //     'name_id' => 'dilu',
            //     'route_name' => null,
            //     'icon' => null,
            //     'parent_slug' => null,
            //     'order' => 4,
            //     'is_active' => false,
            //     'permission_name' => null,
            // ],
        ];

        DB::beginTransaction();

        try {
            $masterSlugs = $insertedSlugs = $skippedSlugs = $updatedSlugs = $deletedSlugs = [];

            $existingMenus = DB::table('menus')->get()->keyBy('slug');      //ambil semua dulu biar gak N+1, tapi kalau menu masih kecil gpp di-first dalam loop
            foreach ($menus as $item) {
                $masterSlugs[] = $item['slug'];
                $existing = $existingMenus[$item['slug']] ?? null;

                // search parent_id
                $parentId = null;
                if (!empty($item['parent_slug'])) {
                    $parent = $existingMenus[$item['parent_slug']] ?? null;
                    $parentId = $parent ? $parent->id : null;
                }

                $isChanged = !$existing ||
                             $existing->parent_id != $parentId ||
                             $existing->name_en !== $item['name_en'] ||
                             $existing->name_id !== $item['name_id'] ||
                             $existing->route_name !== $item['route_name'] ||
                             $existing->icon !== $item['icon'] ||
                             $existing->order != $item['order'] ||
                             (bool) $existing->is_active !== (bool) $item['is_active'] ||
                             $existing->permission_name !== $item['permission_name'];
                
                if ($existing && !$isChanged){
                    $skippedSlugs[] = $item['slug'];
                    continue;
                }

                // Validation
                $newData = [
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
                $validator = Validator::make($newData, [
                    'slug' => 'required|string|max:50',
                    'parent_id' => 'nullable|exists:menus,id',
                    'name_en' => 'required|string|max:50',
                    'name_id' => 'required|string|max:50',
                    'route_name' => 'nullable|string|max:255',
                    'icon' => 'nullable|string|max:255',
                    'order' => [
                        'required',
                        'integer',
                        Rule::unique('menus')->where(function ($query) use ($parentId) { return $query->where('parent_id', $parentId); })
                                             ->when($existing, function ($rule) use ($existing) { return $rule->ignore($existing->id); }), //ignore row sendiri
                    ],
                    'is_active' => 'boolean',
                    'permission_name' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $errors = json_encode($validator->errors()->all());
                    throw new \Exception("Failed to validate menu: '{$item['slug']}': {$errors}");
                }

                // UPDATE or INSERT
                if ($existing) {
                    // if ($isChanged) {
                        DB::table('menus')
                            ->where('slug', $item['slug'])
                            ->update([
                                'parent_id' => $parentId,
                                'name_en' => $item['name_en'],
                                'name_id' => $item['name_id'],
                                'route_name' => $item['route_name'],
                                'icon' => $item['icon'],
                                'order' => $item['order'],
                                'is_active' => $item['is_active'],
                                'permission_name' => $item['permission_name'],
                                'updated_at' => now(),
                                'updated_by' => 1,
                            ]);
                        $updatedSlugs[] = $item['slug'];
                    // }
                } else {
                    DB::table('menus')->insert([
                        'ulid' => (string) Str::ulid(),
                        'slug' => $item['slug'],
                        'parent_id' => $parentId,
                        'name_en' => $item['name_en'],
                        'name_id' => $item['name_id'],
                        'route_name' => $item['route_name'],
                        'icon' => $item['icon'],
                        'order' => $item['order'],
                        'is_active' => $item['is_active'],
                        'permission_name' => $item['permission_name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]);
                    $insertedSlugs[] = $item['slug'];
                }
            }

            // Hapus semua menu di db yang slug-nya TIDAK ADA di $menus / $masterSlugs
            $toDelete = DB::table('menus')
                            ->whereNotIn('slug', $masterSlugs)
                            ->pluck('slug');
            $deletedSlugs = $toDelete->toArray();

            DB::table('menus')->whereNotIn('slug', $masterSlugs)->delete();


            DB::commit();
            Log::info('MENU SYNC SEEDER' . ': Process committed' , [
                'inserted' => [
                    'count' => count($insertedSlugs),
                    'slugs' => $insertedSlugs,
                ],
                'updated' => [
                    'count' => count($updatedSlugs),
                    'slugs' => $updatedSlugs,
                ],
                'skipped' => [
                    'count' => count($skippedSlugs),
                    'slugs' => $skippedSlugs,
                ],
                'deleted' => [
                    'count' => count($deletedSlugs),
                    'slugs' => $deletedSlugs,
                ],
            ]);

            $this->command->info("=========== MENU SYNC RESULT ===========");
            $this->command->info("Inserted (" . count($insertedSlugs) . "):");
            $this->command->line(implode(', ', $insertedSlugs));
            $this->command->info("Updated (" . count($updatedSlugs) . "):");
            $this->command->line(implode(', ', $updatedSlugs));
            $this->command->info("Skipped (" . count($skippedSlugs) . "):");
            $this->command->line(implode(', ', $skippedSlugs));
            $this->command->info("Deleted (" . count($deletedSlugs) . "):");
            $this->command->line(implode(', ', $deletedSlugs));
            $this->command->newLine(); 

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MENU SYNC SEEDER' . ': Process Failed at line ' . __LINE__ , [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->command->error("An error occurred: " . $e->getMessage());
            throw $e;
        }
    }
}
