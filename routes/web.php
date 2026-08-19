<?php

use App\Http\Controllers\Development\ComponentController;
use App\Http\Controllers\Sysadmin\MenuController;
use App\Http\Controllers\Sysadmin\PermissionController;
use App\Http\Controllers\Sysadmin\RolePermissionController;
use App\Http\Controllers\Sysadmin\UserController;
use App\Http\Controllers\Sysadmin\UserPermissionController;
use App\Http\Controllers\WorkCalendar\WorkCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return view('layouts.green_layout');
});

// ===============
//    SYSADMIN
// ===============
Route::prefix('sysadmin')->name('sysadmin.')->group(function(){

    // MENU
    Route::prefix('menus')->name('menus.')->group(function(){

        Route::resource('/', MenuController::class);
    });

    // USER
    Route::prefix('users')->name('users.')->group(function(){

        Route::resource('/', UserController::class);
    });

    // ROLE
    Route::prefix('role-permissions')->name('role_permissions.')->group(function(){

        Route::get('/ajax/roles', [RolePermissionController::class, 'getRoles'])->name('ajax.roles');
        Route::get('/ajax/roles/{id}/permissions', [RolePermissionController::class, 'getRolePermissions'])->name('ajax.role_permissions');
        Route::put('/ajax/roles/{id}/sync-permissions', [RolePermissionController::class, 'syncPermissions'])->name('ajax.sync_permissions');
        Route::post('/ajax', [RolePermissionController::class, 'store'])->name('ajax.store');
        Route::put('/ajax/roles/{id}', [RolePermissionController::class, 'update'])->name('ajax.update');
        Route::delete('/ajax/roles/{id}', [RolePermissionController::class, 'destroy'])->name('ajax.destroy');
    
        Route::resource('/', RolePermissionController::class);
    });

    // PERMISSION
    Route::prefix('permissions')->name('permissions.')->group(function(){

        Route::get('/ajax/all', [PermissionController::class, 'getAll'])->name('ajax.all');
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
