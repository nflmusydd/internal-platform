<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // MASTER DATA MENU
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
                'is_active' => true,
                'permission_name' => 'view_work_calendar',
            ],
        ];

        DB::beginTransaction();

        try {
            $masterSlugs = []; 

            foreach ($menus as $item) {
                $masterSlugs[] = $item['slug'];

                // search parent_id
                $parentId = null;
                if (!empty($item['parent_slug'])) {
                    $parent = DB::table('menus')->where('slug', $item['parent_slug'])->first();
                    $parentId = $parent ? $parent->id : null;
                }

                // Validation
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
                        })
                    ],
                    'is_active' => 'boolean',
                    'permission_name' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $errors = json_encode($validator->errors()->all());
                    throw new \Exception("Failed to validate menu: '{$item['slug']}': {$errors}");
                }

                // INSERT or UPDATE
                $existingSlug = DB::table('menus')->where('slug', $item['slug'])->first();
                if ($existingSlug) {
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
                }
            }

            // Hapus semua menu di db yang slug-nya TIDAK ADA di $menus / $masterSlugs
            $deletedCount = DB::table('menus')
                            ->whereNotIn('slug', $masterSlugs)
                            ->delete();

            if ($deletedCount > 0) {
                $this->command->warn("{$deletedCount} Previous menu removed successfully!");
            }

            DB::commit();
            $this->command->info("Menu synced successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("An error occurred: " . $e->getMessage());
            throw $e;
        }
    }
}
