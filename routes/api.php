<?php

use App\Http\Controllers\Api\User\UserPredictionController;
use App\Http\Controllers\Api\User\UserTicketController;
use App\Http\Controllers\Api\User\UserVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\User\UserAuthController;
use App\Http\Controllers\Api\User\UserTransactionController;
use App\Http\Controllers\Common\CommonController;
use App\Http\Controllers\Api\User\UserHomeController;
use App\Http\Controllers\Api\User\UserProfileController;
use App\Http\Controllers\Api\User\UserPurchaseController;
use App\Http\Controllers\Api\User\UserScoreController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api', 'prefix' => 'v1/user'], function () {
    Route::post('register', [UserAuthController::class, 'register']);
    Route::post('login', [UserAuthController::class, 'login']);
    Route::get('get-countries', [UserHomeController::class, 'getCountries'])->name('user.leader-board');
    Route::get('tickets', [UserTicketController::class, 'getTickets']);


    Route::group(['middleware' => 'auth:api', 'api_auth'], function () {


        Route::post('send-otp', [UserVerificationController::class, 'sendOtp']);
        Route::post('verify-otp', [UserVerificationController::class, 'verifyOtp']);
        Route::post('resend-otp', [UserVerificationController::class, 'resendOtp']);

        Route::get('get-profile', [UserProfileController::class, 'getProfile']);
        Route::post('update-social-media', [UserProfileController::class, 'updateSocialMedia']);
        Route::post('update-profile', [UserProfileController::class, 'updateProfile']);
        Route::post('update-password', [UserProfileController::class, 'updatePassword']);

        Route::post('purchase-ticket', [UserTicketController::class, 'purchaseTicket']);
        Route::get('my-tickets', [UserTicketController::class, 'getMyTickets']);

        Route::get('transactions', [UserTransactionController::class, 'index']);

        Route::get('my-questions', [UserPredictionController::class, 'myQuestions']);
        Route::post('submit-answer', [UserPredictionController::class, 'submitAnswer']);
        Route::get('my-predictions', [UserPredictionController::class, 'myPredictions']);

        Route::any('logout', [UserAuthController::class, 'userLogout'])->name('user.logout');
    });

});
