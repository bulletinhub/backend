<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::group([
    'middleware' => 'api',
], function ($router) {
    Route::post('/user/signup', [UserController::class, 'store']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::post('/user/{id}', [UserController::class, 'update']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('signin', [AuthController::class, 'login']);
    Route::post('signout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// 
// Route::group([
//     'middleware' => 'api',
//     'prefix' => 'filter'
// ], function ($router) {
//     Route::get('/user/{id}', [UserController::class, 'filtersByUserId']);
//     Route::post('/', [UserController::class, 'store']);
//     Route::get('/{id}', [UserController::class, 'show']);
//     Route::post('/{id}', [UserController::class, 'update']);
//     Route::delete('/{id}', [UserController::class, 'destroy']);
// });