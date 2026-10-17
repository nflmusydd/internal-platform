<?php

namespace App\Providers;

use App\Models\Menu;
use App\Support\RoutePermission;
use Illuminate\Support\Facades\Gate;
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
        // Super Admin bypasses every permission / ability check. Returning null
        // for everyone else lets Spatie's own Gate::before handler run.
        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('SUPER ADMIN') ? true : null;
        });

        // ==========================
        //  LOAD MENU DI SIDEBAR
        // ==========================
        View::composer('layouts.iplat1_layout1', function ($view) {
            $user = auth()->user();

            // Ambil menu aktif dengan anak-anaknya
            $menus = Menu::whereNull('parent_id')
                        ->where('is_active', true)
                        ->with(['children' => function($q) {
                            $q->where('is_active', true)->orderBy('order');
                        }])
                        ->orderBy('order')
                        ->get();

            // Filter berdasarkan permission
            // menu folder tampil bila minimal satu anaknya tampil
            $menus = $menus
                ->map(function (Menu $menu) use ($user) {
                    if ($menu->children->isNotEmpty()) {
                        $children = $menu->children
                            ->filter(fn (Menu $child) => RoutePermission::allows($user, $child->route_name))
                            ->values();

                        if ($children->isEmpty()) {
                            return null;
                        }

                        $menu->setRelation('children', $children);

                        return $menu;
                    }

                    return RoutePermission::allows($user, $menu->route_name) ? $menu : null;
                })
                ->filter()
                ->values();

            $view->with('menus', $menus);
        });
    }
}
