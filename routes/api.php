<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\PaymentsController;
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
    Route::get('/series',[SeriesController::class,'series']);
    Route::get('/feature', [MovieController::class,'feature_movie']);
    Route::get('/popular', [MovieController::class,'popular']);
    Route::get('/originals', [MovieController::class,'originals']);
    Route::get('/new', [MovieController::class,'new_release']);
    Route::get('/coming_soon', [MovieController::class,'coming_soon']);
    Route::get('/movies', [MovieController::class,'movies']);
    Route::get('/movies/{id}', [MovieController::class,'movie_by_title']);
    Route::get('/all/popular', [MovieController::class,'all_popular']);
    Route::get('/all/feature', [MovieController::class,'all_feature_movies']);
    Route::get('/all/coming_soon', [MovieController::class,'all_coming_soon']);
    Route::get('/all/series', [MovieController::class,'all_series']);
    Route::get('/all/movies', [MovieController::class,'all_movies']);
    Route::get('/front/video', [MovieController::class,'front_video']);
    Route::get('/front/video', [MovieController::class,'front_video']);
    Route::post('/zamtel/pay', [PaymentsController::class, 'zamtelPay']);
    Route::post('/mtn/pay', [PaymentsController::class, 'mtnPay']);
    Route::post('/mtn/check', [PaymentsController::class, 'ConfirmPayment']);
    Route::get('/movie/title', [MovieController::class, 'movie_title']);
    Route::post('/user/status', [PaymentsController::class, 'userStatus']);
    Route::get('/user/logout',[UserController::class,'logout']);
});
