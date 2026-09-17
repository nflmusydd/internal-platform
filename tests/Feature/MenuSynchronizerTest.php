<?php

namespace Tests\Feature;

use App\Support\MenuSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MenuSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    private function siblingOrders(): array
    {
        return DB::table('menus')
            ->whereIn('slug', ['sysadmin/menus', 'sysadmin/users'])
            ->orderBy('slug')
            ->pluck('order', 'slug')
            ->all();
    }

    public function test_fresh_sync_builds_tree_with_correct_parents(): void
    {
        $this->assertSame(0, DB::table('menus')->count());

        $result = MenuSynchronizer::sync();

        $this->assertCount(count(MenuSynchronizer::catalog()), $result['inserted']);

        $sysadmin = DB::table('menus')->where('slug', 'sysadmin')->first();
        $child = DB::table('menus')->where('slug', 'sysadmin/menus')->first();
        $this->assertNull($sysadmin->parent_id);
        $this->assertSame((int) $sysadmin->id, (int) $child->parent_id);

        $dev = DB::table('menus')->where('slug', 'development')->first();
        $devChild = DB::table('menus')->where('slug', 'development/components')->first();
        $this->assertSame((int) $dev->id, (int) $devChild->parent_id);
    }

    public function test_second_sync_is_idempotent(): void
    {
        MenuSynchronizer::sync();
        $result = MenuSynchronizer::sync();

        $this->assertCount(count(MenuSynchronizer::catalog()), $result['skipped']);
        $this->assertEmpty($result['inserted']);
        $this->assertEmpty($result['updated']);
        $this->assertEmpty($result['deleted']);
    }

    public function test_reorder_swap_does_not_collide(): void
    {
        MenuSynchronizer::sync();

        DB::table('menus')->where('slug', 'sysadmin/menus')->update(['order' => 99]);
        DB::table('menus')->where('slug', 'sysadmin/users')->update(['order' => 1]);

        $result = MenuSynchronizer::sync();

        $this->assertSame(['sysadmin/menus' => 1, 'sysadmin/users' => 2], $this->siblingOrders());
        $this->assertContains('sysadmin/menus', $result['updated']);
        $this->assertContains('sysadmin/users', $result['updated']);
    }

    public function test_reinsert_deleted_child_into_existing_scope(): void
    {
        MenuSynchronizer::sync();
        DB::table('menus')->where('slug', 'sysadmin/users')->delete();

        $result = MenuSynchronizer::sync();

        $this->assertContains('sysadmin/users', $result['inserted']);

        $sysadmin = DB::table('menus')->where('slug', 'sysadmin')->first();
        $row = DB::table('menus')->where('slug', 'sysadmin/users')->first();
        $this->assertSame((int) $sysadmin->id, (int) $row->parent_id);
        $this->assertSame(2, (int) $row->order);
    }

    public function test_reinsert_parent_and_child_tree(): void
    {
        MenuSynchronizer::sync();
        DB::table('menus')->where('slug', 'development')->delete();

        $result = MenuSynchronizer::sync();

        $this->assertContains('development', $result['inserted']);
        $this->assertContains('development/components', $result['inserted']);

        $dev = DB::table('menus')->where('slug', 'development')->first();
        $child = DB::table('menus')->where('slug', 'development/components')->first();
        $this->assertNull($dev->parent_id);
        $this->assertSame((int) $dev->id, (int) $child->parent_id);
    }

    public function test_root_reorder_swap_does_not_collide(): void
    {
        MenuSynchronizer::sync();

        DB::table('menus')->where('slug', 'sysadmin')->update(['order' => 98]);
        DB::table('menus')->where('slug', 'development')->update(['order' => 1]);
        DB::table('menus')->where('slug', 'sysadmin')->update(['order' => 2]);

        $result = MenuSynchronizer::sync();

        $roots = DB::table('menus')
            ->whereIn('slug', ['sysadmin', 'development'])
            ->pluck('order', 'slug')
            ->all();

        $this->assertEquals(['sysadmin' => 1, 'development' => 2], $roots);
        $this->assertContains('sysadmin', $result['updated']);
        $this->assertContains('development', $result['updated']);
    }
}
