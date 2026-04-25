<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\Common\LanguageController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\User\KitLibraryController;
use App\Http\Controllers\User\AuthController;



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



//Route::get('/home', [HomeController::class, 'index'])->name('home');
//Route::get('/test_time_zone', [\App\Http\Controllers\TestController::class, 'testTimeZone']);
//Route::get('/test_code', [\App\Http\Controllers\TestController::class, 'testCode']);

Route::get('/', [TestController::class, 'homePage']);


// User

// Auth
Route::group(['middleware' => 'guest:web'], function () {
    Route::group(['middleware' => 'prevent-back-history'], function () {
    });
});

// Non Authenticated
Route::group(['middleware' => 'prevent-back-history'], function () {
    Route::get('change-language',[LanguageController::class,'changeLanguage'])->name('change-language');
});

Route::group(['middleware' => 'auth:web', 'prefix' => 'user'], function () {
    Route::group(['middleware' => 'prevent-back-history'], function () {
        Route::get('logout', [AuthController::class, 'userLogout'])->name('logout');
    });
});
