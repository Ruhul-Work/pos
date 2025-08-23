<?php
use App\Http\Controllers\admin\PermissionController;
use App\Http\Controllers\admin\RoleController;
use App\Http\Controllers\admin\UserPermissionController;
use App\Http\Controllers\admin\UserController;
use Illuminate\Support\Facades\Route;


// ==== User Management ====
Route::prefix('usermanage')->name('usermanage.')->middleware(['web','auth','perm'])->group(function () {
     // ←(Perm) একবার দিলেই গ্রুপের সব রুটে অটো ability detect হবে
    //=== Users management routes
    Route::get('users', [UserController::class,'index'])->name('users.index');
    Route::get('users/create', [UserController::class,'create'])->name('users.create');   
    Route::post('users',        [UserController::class,'store'])->name('users.store'); 
    Route::get('users/{user}/edit', [UserController::class,'edit'])->name('users.edit');   
    Route::put('users/{user}',      [UserController::class,'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class,'destroy'])->name('users.destroy'); 

    Route::get('profile', fn() => 'My Profile')->name('users.profile');       
    //=== Individual User Permission Overrides
    Route::get('users/{user}/userpermission', [UserPermissionController::class, 'edit'])->name('userspermission.edit');     
    Route::post('users/{user}/userpermission', [UserPermissionController::class, 'update'])->name('userspermission.update');  
        

});


  // === RBAC (Role & Permission routes)===
Route::prefix('rbac')
    ->name('rbac.')
    ->middleware(['web', 'auth', 'perm']) 
    ->group(function () {

 // === RBAC (Permission routes)====
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('permissions/create', [PermissionController::class,'create'])->name('permissions.create');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store'); 
        Route::post('permissions/{permission}/routes', [PermissionController::class, 'attachRoute'])->name('permissions.routes.attach'); 
        Route::delete('permissions/{permission}/routes/{routeName}', [PermissionController::class, 'detachRoute'])->name('permissions.routes.detach'); 

 // === RBAC (Role routes)====
        Route::get('role', [RoleController::class,'index'])->name('role.index');     
        //role matrix Save (bulk upsert)
        Route::post('role/save', [RoleController::class,'save'])->name('role.save');   
        Route::get('role/create', [RoleController::class,'create'])->name('role.create'); 
        Route::post('role',        [RoleController::class,'store'])->name('role.store'); 

    });
