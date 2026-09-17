<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Development\ComponentController;
use App\Http\Controllers\Sysadmin\MenuController;
use App\Http\Controllers\Sysadmin\PermissionController;
use App\Http\Controllers\Sysadmin\RolePermissionController;
use App\Http\Controllers\Sysadmin\UserController;
use App\Http\Controllers\Sysadmin\UserPermissionController;
use App\Http\Controllers\WorkCalendar\WorkCalendarController;
use Illuminate\Support\Facades\Route;

// ===============
//     AUTH
// ===============
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ===============
//   PROTECTED
// ===============
Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return view('layouts.iplat1_layout1');
    });

    // LOCALE
    Route::get('/locale/{locale}', function (string $locale) {
        if (in_array($locale, ['en', 'id'])) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    })->name('locale');

    // ===============
    //    SYSADMIN
    // ===============
    Route::prefix('sysadmin')->name('sysadmin.')->group(function(){

        // MENU
        Route::prefix('menus')->name('menus.')->group(function(){
            Route::get('/ajax/menus', [MenuController::class, 'getAll'])->name('ajax.all');
            Route::get('/ajax/export-menus', [MenuController::class, 'exportMenus'])->name('ajax.export_menus');
            Route::resource('/', MenuController::class)->only('index');
        });

        // USER
        Route::prefix('users')->name('users.')->group(function(){
            Route::get('/ajax/users', [UserController::class, 'getUsers'])->name('ajax.users');
            Route::get('/ajax/export-users', [UserController::class, 'exportUsers'])->name('ajax.export_users');
            Route::post('/ajax', [UserController::class, 'store'])->name('ajax.store');
            Route::put('/ajax/{id}', [UserController::class, 'update'])->name('ajax.update');
            Route::delete('/ajax/{id}', [UserController::class, 'destroy'])->name('ajax.destroy');
            Route::resource('/', UserController::class);
        });

        // ROLE
        Route::prefix('role-permissions')->name('role_permissions.')->group(function(){
            Route::get('/ajax/roles', [RolePermissionController::class, 'getRoles'])->name('ajax.roles');
            Route::get('/ajax/export-roles', [RolePermissionController::class, 'exportRoles'])->name('ajax.export_roles');
            Route::get('/ajax/roles/{id}/permissions', [RolePermissionController::class, 'getRolePermissions'])->name('ajax.role_permissions');
            Route::get('/ajax/roles/{id}/users', [RolePermissionController::class, 'getRoleUsers'])->name('ajax.role_users');
            Route::put('/ajax/roles/{id}/sync-permissions', [RolePermissionController::class, 'syncPermissions'])->name('ajax.sync_permissions');
            Route::post('/ajax', [RolePermissionController::class, 'store'])->name('ajax.store');
            Route::put('/ajax/roles/{id}', [RolePermissionController::class, 'update'])->name('ajax.update');
            Route::delete('/ajax/roles/{id}', [RolePermissionController::class, 'destroy'])->name('ajax.destroy');
            Route::resource('/', RolePermissionController::class);
        });

        // PERMISSION
        Route::prefix('permissions')->name('permissions.')->group(function(){
            Route::get('/ajax/all', [PermissionController::class, 'getAll'])->name('ajax.all');
            Route::get('/ajax/permissions/{id}/roles', [PermissionController::class, 'getPermissionRoles'])->name('ajax.permission_roles');
            Route::get('/ajax/export-permissions', [PermissionController::class, 'exportPermissions'])->name('ajax.export_permissions');
            Route::post('/ajax', [PermissionController::class, 'store'])->name('ajax.store');
            Route::put('/ajax/{id}', [PermissionController::class, 'update'])->name('ajax.update');
            Route::delete('/ajax/{id}', [PermissionController::class, 'destroy'])->name('ajax.destroy');
        });

        // USER PERMISSION
        Route::prefix('user-permissions')->name('user_permissions.')->group(function(){
            Route::resource('/', UserPermissionController::class);
        });
    });

    // ===============
    //   DEVELOPMENT
    // ===============
    Route::prefix('development')->name('development.')->group(function(){
        Route::prefix('components')->name('components.')->group(function(){
            Route::resource('/', ComponentController::class);
        });
    });

    // ==================
    //    WORK CALENDAR
    // ==================
    Route::prefix('work-calendar')->name('work_calendar.')->group(function(){
        Route::resource('/', WorkCalendarController::class);
    });
});
