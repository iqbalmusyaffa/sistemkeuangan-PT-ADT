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
use App\Http\Controllers\Api\ProyekController;
use App\Http\Controllers\Api\TerminController;
use App\Http\Controllers\Api\ServiceCategoryController;
use App\Http\Controllers\Api\InvoiceController;

use App\Http\Controllers\Api\ActivityController;
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
    Route::get('/proyeks/{proyekId}/expenses', [ExpenseController::class, 'getByProject']);
    Route::post('/proyeks/{proyekId}/expenses', [ExpenseController::class, 'storeForProject']);
    // Merek routes
    Route::apiResource('mereks', MerekController::class);
    // Unit routes
    Route::apiResource('units', UnitsController::class);
    // Purchasematerial routes
    Route::apiResource('purchasematerials', PurchasematerialController::class);
    // Proyek routes
    Route::apiResource('proyeks', ProyekController::class);

    // Termin routes
    Route::apiResource('termins', TerminController::class);
    Route::get('/proyeks/{proyekId}/termins', [TerminController::class, 'getByProject']);
    Route::put('/termins/{termin}/status', [TerminController::class, 'updateStatus']);

    // Additional proyek-related routes
    Route::get('proyeks/{id}/termins', [ProyekController::class, 'getTermins']);
    Route::get('proyeks/{id}/purchase-materials', [ProyekController::class, 'getPurchaseMaterials']);

    Route::post('/logout', [UserController::class, 'logout']);

    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('service-categories', ServiceCategoryController::class);
    Route::get('/activity-log', [ActivityController::class, 'index']);
});
Route::middleware('auth:sanctum')->get('/profile', [UserController::class, 'profile']);
