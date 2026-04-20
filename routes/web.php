<?php

use App\Http\Controllers\Development\ComponentController;
use App\Http\Controllers\Sysadmin\MenuController;
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
    //     // Dashboard
    //     Route::post('data','xxx\DeploymentRequestController@data')->name('data');
    //     Route::get('change-role/{role}', 'xxx\DeploymentRequestController@changeRole')->name('change_role');
    //     Route::post('chooser-user/{role}', 'xxx\DeploymentRequestController@userChooser')->name('chooser');
    //     Route::delete('cancelTR/{id}', 'xxx\DeploymentRequestController@cancelTRHeader')->name('cancelTR');
    //     Route::get('export_excel', 'xxx\DeploymentRequestController@exportExcel')->name('export_excel');
        
        Route::resource('/', MenuController::class);
    });

    // USER
    Route::prefix('users')->name('users.')->group(function(){

        Route::resource('/', UserController::class);
    });

    // ROLE PERMISSION
    Route::prefix('role-permissions')->name('role_permissions.')->group(function(){

        Route::resource('/', RolePermissionController::class);
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
