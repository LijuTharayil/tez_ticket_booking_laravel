<?php

use App\Http\Controllers\Admin\AdminQuestionController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\AdminUserController;
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

    Route::post('/login',[AdminAuthController::class,'adminLogin'])->name('admin.login');

    // Authenticated Routes
    Route::group(['middleware' => ['auth:admin']], function () {
            Route::get('dashboard', [AdminDashboardController::class, 'getAdminDashboard'])->name('admin.dashboard')->middleware('check_admin_role');

            Route::get('users', [AdminUserController::class, 'getUsers'])->name('admin.users');
            Route::post('approve-social-media', [AdminUserController::class, 'approveSocialMedia'])->name('admin.approve.social.media');

            Route::prefix('tickets')->group(function () {
                Route::get('/', [AdminTicketController::class, 'getTickets']);
                Route::post('/add', [AdminTicketController::class, 'addTicket']);
                Route::post('/edit', [AdminTicketController::class, 'editTicket']);
                Route::post('/delete', [AdminTicketController::class, 'deleteTicket']);
            });

            Route::prefix('questions')->group(function () {
                Route::get('/', [AdminQuestionController::class, 'listQuestions']);
                Route::post('/add', [AdminQuestionController::class, 'addQuestion']);
                Route::post('/edit', [AdminQuestionController::class, 'editQuestion']);
                Route::post('/delete', [AdminQuestionController::class, 'deleteQuestion']);
            });

            Route::get('transactions', [AdminTicketController::class, 'transactionsList'])->name('admin.transactions');


            Route::post('logout', [AdminAuthController::class, 'adminLogout'])->name('admin.logout');
    });

});
