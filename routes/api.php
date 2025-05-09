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
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProfitLossReportController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\KasbonController;

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

// Kategori routes - moved outside auth middleware for testing
Route::apiResource('kategori', KategoriTransaksiController::class);

Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // });
    Route::apiResource('users', UserController::class);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/summary', [\App\Http\Controllers\Api\DashboardController::class, 'summary']);
    Route::get('/dashboard/chart-summary', [\App\Http\Controllers\Api\DashboardController::class, 'chartSummary']);

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

    // Payment Method Routes
    Route::apiResource('payment-methods', PaymentMethodController::class);

    // Profit Loss Report Routes
    Route::get('/profit-loss-reports', [ProfitLossReportController::class, 'index']);
    //generate report
    Route::post('/profit-loss-reports/generate', [ProfitLossReportController::class, 'generateReport']);
    //get report by id
    Route::get('/profit-loss-reports/{report}', [ProfitLossReportController::class, 'show']);
    Route::get('/profit-loss', [\App\Http\Controllers\Api\ProfitLossReportController::class, 'summary']);
    Route::get('/profit-loss/recap', [\App\Http\Controllers\Api\ProfitLossReportController::class, 'recap']);

    // Budget Routes
    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);
    Route::get('/budgets/{budget}', [BudgetController::class, 'show']);
    Route::put('/budgets/{budget}', [BudgetController::class, 'update']);
    Route::post('/budgets/{budget}/transactions', [BudgetController::class, 'addTransaction']);
    Route::post('/budget-transactions/{transaction}/approve', [BudgetController::class, 'approveTransaction']);
    Route::post('/budget-transactions/{transaction}/reject', [BudgetController::class, 'rejectTransaction']);

    // Kasbon Routes
    Route::get('/kasbons/export-pdf', [KasbonController::class, 'exportPdf']);
    Route::get('/kasbons/export-excel', [KasbonController::class, 'exportExcel']);
    Route::get('/kasbons', [KasbonController::class, 'index']);
    Route::post('/kasbons', [KasbonController::class, 'store']);
    Route::get('/kasbons/{kasbon}', [KasbonController::class, 'show']);
    Route::put('/kasbons/{kasbon}', [KasbonController::class, 'update']);
    Route::delete('/kasbons/{kasbon}', [KasbonController::class, 'destroy']);
    Route::post('/kasbons/{kasbon}/upload-attachment', [KasbonController::class, 'uploadAttachment']);
    Route::delete('/kasbons/{kasbon}/attachments/{attachment}', [KasbonController::class, 'deleteAttachment']);
    Route::post('/kasbons/{kasbon}/approve', [KasbonController::class, 'approve']);
    Route::post('/kasbons/{kasbon}/reject', [KasbonController::class, 'reject']);
    Route::post('/kasbons/{kasbon}/payments', [KasbonController::class, 'addPayment']);
    Route::get('/kasbon-attachments/{attachment}/download', [KasbonController::class, 'downloadAttachment']);

    // ini adalah route untuk download file excel
    Route::get('/termins/export-pdf/{project}', [TerminController::class, 'exportPDF']);
    Route::get('/termins/export-excel/{project}', [TerminController::class, 'exportExcel']);
    Route::post('/termins/import-excel', [TerminController::class, 'importExcel']);
});
Route::middleware('auth:sanctum')->get('/profile', [UserController::class, 'profile']);
