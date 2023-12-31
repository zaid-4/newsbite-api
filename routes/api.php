<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\NewsController;
use App\Http\Controllers\API\UsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication Routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);

Route::get('/all-news', [NewsController::class, 'index']);
Route::get('/all-news/{id}', [NewsController::class, 'show']);
Route::get('/all-news-meta', [NewsController::class, 'getNewsMeta']);

// User CRUD Routes
Route::middleware('auth:sanctum')->group(function () {
    // User CRUD Routes
    Route::put('/update-profile', [UsersController::class, 'update']);
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    Route::get('/news-meta', [NewsController::class, 'getNewsMeta']);
    Route::put('/user-prefrences', [UsersController::class, 'updatePrefrences']);
});
