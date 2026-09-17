<?php

namespace App\Http\Controllers\Sysadmin;

use App\Exports\MenusExport;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('sysadmin.menus.index');
    }

    /**
     * Return the full menu tree as a flat, ordered list (read-only data source).
     */
    public function getAll()
    {
        $menus = Menu::query()->get();

        return response()->json(['data' => $this->flattenTree($menus)]);
    }

    /**
     * Export the menu tree (respecting active filters) as XLSX.
     */
    public function exportMenus(Request $request)
    {
        $query = Menu::query();

        if ($request->filled('name')) {
            $name = $request->name;
            $query->where(function ($q) use ($name) {
                $q->where('name_en', 'LIKE', '%' . $name . '%')
                  ->orWhere('name_id', 'LIKE', '%' . $name . '%')
                  ->orWhere('slug', 'LIKE', '%' . $name . '%');
            });
        }
        if ($request->filled('parent')) {
            $parent = $request->parent;
            $query->whereHas('parent', function ($q) use ($parent) {
                $q->where('name_en', $parent)->orWhere('name_id', $parent);
            });
        }
        if ($request->filled('status')) {
            $query->where('is_active', (bool) $request->status);
        }
        if ($request->filled('created_at_from')) {
            $query->where('created_at', '>=', Carbon::createFromFormat('d/m/Y', $request->created_at_from)->startOfDay());
        }
        if ($request->filled('created_at_to')) {
            $query->where('created_at', '<=', Carbon::createFromFormat('d/m/Y', $request->created_at_to)->endOfDay());
        }
        if ($request->filled('updated_at_from')) {
            $query->where('updated_at', '>=', Carbon::createFromFormat('d/m/Y', $request->updated_at_from)->startOfDay());
        }
        if ($request->filled('updated_at_to')) {
            $query->where('updated_at', '<=', Carbon::createFromFormat('d/m/Y', $request->updated_at_to)->endOfDay());
        }

        $rows = $this->flattenTree($query->get());

        return (new MenusExport($rows))->download('menus');
    }

    /**
     * Flatten a menu collection into ordered rows carrying their tree metadata.
     *
     * Nodes whose parent is not part of the collection are treated as roots so
     * that filtered result sets still render as a sensible hierarchy.
     */
    private function flattenTree($menus): array
    {
        $byId = $menus->keyBy('id');
        $children = $menus->groupBy(fn ($menu) => $menu->parent_id ?? 0);
        $locale = app()->getLocale();

        $rows = [];
        $walk = function ($node, $depth) use (&$walk, &$rows, $children, $byId, $locale) {
            $parent = $node->parent_id ? $byId->get($node->parent_id) : null;
            $parentName = null;
            if ($parent) {
                $parentName = $locale === 'id'
                    ? ($parent->name_id ?: $parent->name_en)
                    : ($parent->name_en ?: $parent->name_id);
            }
            $childNodes = $children->get($node->id);

            $rows[] = [
                'ulid' => $node->ulid,
                'slug' => $node->slug,
                'name_en' => $node->name_en,
                'name_id' => $node->name_id,
                'route_name' => $node->route_name,
                'permission_name' => $node->permission_name,
                'icon' => $node->icon,
                'order' => $node->order,
                'is_active' => (bool) $node->is_active,
                'created_at' => $node->created_at,
                'updated_at' => $node->updated_at,
                'depth' => $depth,
                'parent_id' => $node->parent_id,
                'parent_name' => $parentName,
                'has_children' => $childNodes ? $childNodes->isNotEmpty() : false,
            ];

            if ($childNodes) {
                foreach ($childNodes->sortBy('order') as $child) {
                    $walk($child, $depth + 1);
                }
            }
        };

        $roots = $menus->filter(fn ($menu) => !$menu->parent_id || !$byId->has($menu->parent_id))
                       ->sortBy('order');

        foreach ($roots as $root) {
            $walk($root, 0);
        }

        return $rows;
    }
}
