<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\ArticleController;

// User
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

// User Filters
Route::group([
    'middleware' => 'api',
    'prefix' => 'filter'
], function ($router) {
    Route::get('/user/{id}', [FilterController::class, 'filtersByUserId']);
    Route::post('/batch-update', [FilterController::class, 'batchUpdate']);
    Route::post('/', [FilterController::class, 'store']);
    Route::get('/{id}', [FilterController::class, 'show']);
    Route::post('/{id}', [FilterController::class, 'update']);
    Route::delete('/{id}', [FilterController::class, 'destroy']);
});

// Article
Route::group([
    'prefix' => 'article'
], function ($router) {
    Route::get('/', [ArticleController::class, 'index']);
    Route::get('/by-date/{date}', [ArticleController::class, 'getArticleByDate']);
    Route::get('/{id}', [ArticleController::class, 'show']);
});