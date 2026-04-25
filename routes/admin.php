<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Common\LanguageController;
use App\Http\Controllers\Common\SelectController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['admin']], function () {


    // Auth
    Route::group(['middleware' => 'guest:admin'], function () {
        Route::group(['middleware' => 'prevent-back-history'], function () {
            Route::get('/',[AdminAuthController::class,'showAdminLoginForm']);
            Route::get('/login',[AdminAuthController::class,'showAdminLoginForm'])->name('admin.login-view');
            Route::post('/login',[AdminAuthController::class,'adminLogin'])->name('admin.login');
        });
    });

    // Non Authenticated
    Route::group(['middleware' => 'prevent-back-history'], function () {
        Route::get('change-language',[LanguageController::class,'changeLanguage'])->name('admin.change-language');
    });

    // Authenticated Routes
    Route::group(['middleware' => 'auth:admin'], function () {
        Route::group(['middleware' => 'prevent-back-history'], function () {
            Route::get('dashboard', [AdminDashboardController::class, 'getAdminDashboard'])->name('admin.dashboard')->middleware('check_admin_role');

            Route::get('logout', [AdminAuthController::class, 'adminLogout'])->name('admin.logout');
        });

    });

});
