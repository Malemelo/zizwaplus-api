<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::post('/phone-login',[UserController::class,'phoneLoginData']);
Route::post('/email-login',[UserController::class,'emailLoginData']);
Route::post('/phone-register',[UserController::class,'phoneRegisterData']);
Route::post('/email-register',[UserController::class,'emailRegisterData']);
Route::middleware('auth:sanctum', function () {
    Route::get('/series',[SeriesController::class,'index']);
});
