<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KategoriTransaksiController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MerekController;
use App\Http\Controllers\Api\UnitsController;
use App\Http\Controllers\Api\PurchasematerialController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TerminController;
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

// Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // });
    Route::apiResource('users', UserController::class);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Category routes
    Route::apiResource('categories', KategoriTransaksiController::class);
    // Transaction routes
    Route::apiResource('transactions', TransactionController::class);
    // Company routes
    Route::apiResource('companies', CompanyController::class);
    // Income routes
    Route::apiResource('incomes', IncomeController::class);
// Pengeluaran (Expense)
    Route::apiResource('expenses', ExpenseController::class);
    // Merek routes
    Route::apiResource('mereks', MerekController::class);
    // Unit routes
    Route::apiResource('units', UnitsController::class);
    // Purchasematerial routes
    Route::apiResource('purchasematerials', PurchasematerialController::class);
    // Project routes
    Route::apiResource('projects', ProjectController::class);

    // Termin routes
    Route::apiResource('termins', TerminController::class);
    Route::get('/projects/{projectId}/termins', [TerminController::class, 'getByProject']);
    Route::put('/termins/{termin}/status', [TerminController::class, 'updateStatus']);

    Route::post('/logout', [UserController::class, 'logout']);
});
Route::middleware('auth:sanctum')->get('/profile', [UserController::class, 'profile']);
