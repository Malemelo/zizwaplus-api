<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\SeriesController;
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
Route::post('/login',[UserController::class,'emailLoginData']);
Route::post('/phone-register',[UserController::class,'phoneRegisterData']);
Route::post('/register',[UserController::class,'emailRegisterData']);
Route::middleware(['auth:sanctum'])->prefix('zp-u-acc')->group( function(){
    Route::get('/series',[SeriesController::class,'index']);
    Route::get('/feature', [MovieController::class,'feature_movie']);
    Route::get('/popular', [MovieController::class,'popular_movies']);
    Route::get('/originals', [MovieController::class,'originals']);
    Route::get('/new', [MovieController::class,'new_release']);
    Route::get('/coming_soon', [MovieController::class,'coming_soon']);
});
