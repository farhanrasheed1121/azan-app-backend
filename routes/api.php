<?php

use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [AuthController::class, 'signUp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send_otp', [AuthController::class, 'sendOtp']);
Route::post('/verify_otp', [AuthController::class, 'verifyOTP']);
Route::post('/update_password', [AuthController::class, 'updatePassword']);
Route::post('/social_login', [AuthController::class, 'handleSocialiteCallback']);
Route::post('/prayer_time', [UserController::class, 'prayerTime']);
Route::post('/get_post', [UserController::class, 'post']);
Route::post('/al_quran', [UserController::class, 'alQuran']);
Route::post('/post', [UserController::class, 'getPost']);
Route::post('/add_post', [UserController::class, 'addPost']);
Route::post('/get_question', [UserController::class, 'getQuestion']);



Route::middleware(['auth:api'])->group(function () {
    Route::post('/add_islamic', [UserController::class, 'islamicQoute']);
    Route::post('/add_community', [UserController::class, 'communityQoute']);
    Route::post('/add_question', [UserController::class, 'question']);
});
