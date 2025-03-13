<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KategoriTransaksiController;

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

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/categories', [KategoriTransaksiController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Category routes
    Route::post('/categories', [KategoriTransaksiController::class, 'store']);
    Route::get('/categories/{id}', [KategoriTransaksiController::class, 'show']);
    Route::put('/categories/{id}', [KategoriTransaksiController::class, 'update']);
    Route::delete('/categories/{id}', [KategoriTransaksiController::class, 'destroy']);

    Route::post('/logout', [UserController::class, 'logout']);
});
Route::middleware('auth:sanctum')->get('/profile', [UserController::class, 'profile']);
