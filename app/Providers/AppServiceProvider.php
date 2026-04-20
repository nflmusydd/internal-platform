<?php

namespace App\Providers;

use App\Models\Menu;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ==========================
        //  LOAD MENU DI SIDEBAR 
        // ==========================
        View::composer('layouts.green_layout', function ($view) {
            $user = auth()->user();
            
            // Ambil menu aktif dengan anak-anaknya
            $menus = Menu::whereNull('parent_id')
                        ->where('is_active', true)
                        ->with(['children' => function($q) {
                            $q->where('is_active', true)->orderBy('order');
                        }])
                        ->orderBy('order')
                        ->get();

            // // Filter menu berdasarkan permission Spatie
            // $filteredMenus = $menus->filter(function ($menu) use ($user) {
            //     // Jika user adalah Super Admin (opsional), izinkan semua
            //     if ($user?->hasRole('super-admin')) return true;

            //     // Cek permission menu utama
            //     $hasPermission = !$menu->permission_name || $user?->can($menu->permission_name);
                
            //     if ($menu->children->isNotEmpty()) {
            //         // Filter anak-anaknya juga
            //         $menu->setRelation('children', $menu->children->filter(function ($child) use ($user) {
            //             return !$child->permission_name || $user?->can($child->permission_name);
            //         }));
                    
            //         // Jika menu utama tidak punya permission tapi punya anak yang boleh diakses,
            //         // maka menu utama tetap tampil sebagai folder/dropdown
            //         if ($menu->children->isNotEmpty()) return true;
            //     }

            //     return $hasPermission;
            // });

            // $view->with('menus', $filteredMenus);
            $view->with('menus', $menus);
        });
    }
}
