<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\{
    DashboardController, TransactionController, CompanyController,
    IncomeController, ExpenseController, ProyekController,
    TerminController, InvoiceController, PaymentMethodController,
    MerekController, UnitsController, PurchasematerialController,
    ServiceCategoryController, ActivityController,
    ProfitLossReportController, BudgetController,
    KasbonController, NotificationController,
    KategoriTransaksiController
};

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [UserController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (with Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/logout', [UserController::class, 'logout']);
// Route::post('/login', [UserController::class, 'login']);
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/chart-summary', [DashboardController::class, 'chartSummary']);

    /*
    |--------------------------------------------------------------------------
    | Master Data Resources
    |--------------------------------------------------------------------------
    */
    Route::apiResources([
        'users'             => UserController::class,
        'kategori'          => KategoriTransaksiController::class,
        'payment-methods'   => PaymentMethodController::class,
        'companies'         => CompanyController::class,
        'transactions'      => TransactionController::class,
        'mereks'            => MerekController::class,
        'units'             => UnitsController::class,
        'service-categories'=> ServiceCategoryController::class,
    ]);

    Route::post('/mereks/find-or-create', [MerekController::class, 'findOrCreate']);

    /*
    |--------------------------------------------------------------------------
    | Proyek & Terkait
    |--------------------------------------------------------------------------
    */
    Route::apiResource('proyeks', ProyekController::class);
    Route::get('proyeks/{id}/summary', [ProyekController::class, 'summary']);
    Route::get('proyeks/{id}/termins', [ProyekController::class, 'getTermins']);
    Route::get('proyeks/{id}/purchase-materials', [ProyekController::class, 'getPurchaseMaterials']);
    Route::get('proyeks/{proyekId}/expenses', [ExpenseController::class, 'getByProject']);
    Route::post('proyeks/{proyekId}/expenses', [ExpenseController::class, 'storeForProject']);
    Route::get('proyeks/{proyekId}/invoices', [TerminController::class, 'getInvoicesByProject']);
    Route::get('/proyeks/{id}/termins', [ProyekController::class, 'getTermins']);

    /*
    |--------------------------------------------------------------------------
    | Income
    |--------------------------------------------------------------------------
    */
    Route::apiResource('incomes', IncomeController::class);
    Route::post('/incomes/{id}/approve', [IncomeController::class, 'approve']);
    Route::patch('/incomes/{id}/status', [IncomeController::class, 'patchStatus']);
    Route::get('/incomes/total-dp-paid', [IncomeController::class, 'getTotalDpPaid']);
Route::post('/incomes/{id}/revoke', [IncomeController::class, 'revokeApproval']);

    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */
// ✅ Harus di atas!
    Route::get('/expenses/datatables', [ExpenseController::class, 'datatables']);

    // Baru resource di bawah
    Route::apiResource('expenses', ExpenseController::class);
    Route::post('/expenses/{expense}/update-status', [ExpenseController::class, 'updateStatus']);
    Route::post('/expenses/{expense}/upload-bukti', [ExpenseController::class, 'uploadBukti']);


    /*
    |--------------------------------------------------------------------------
    | Termins
    |--------------------------------------------------------------------------
    */
    Route::apiResource('termins', TerminController::class);
    Route::get('/termins/summary', [TerminController::class, 'getTerminSummary']);
    Route::get('/termins/{id}/invoice', [TerminController::class, 'getInvoice']); // untuk Vue
    Route::post('/termins/import-excel', [TerminController::class, 'importExcel']);
    Route::get('/termins/export-excel/{projectId}', [TerminController::class, 'exportExcel']);
    Route::get('/termins/export-pdf/{projectId}', [TerminController::class, 'exportPDF']);
    Route::get('/termins-datatables', [TerminController::class, 'datatables']);
    Route::post('/termins/{termin}/approve-income/{income}', [TerminController::class, 'approveIncome']);
    Route::post('/termins/{termin}/approve-expense/{expense}', [TerminController::class, 'approveExpense']);
    Route::post('/termins/{termin}/record-payment', [TerminController::class, 'recordPayment']);
    Route::post('/termins/{termin}/approve', [TerminController::class, 'approveStatus']);
    Route::post('/termins/{termin}/reject', [TerminController::class, 'rejectStatus']);
    Route::post('/termins/{termin}/update-status', [TerminController::class, 'updateStatus']);
    Route::put('/termins/{termin}/status', [TerminController::class, 'updateStatus']); // alternatif PUT
    Route::post('/termins/{id}/revoke-approval', [TerminController::class, 'revokeApproval']);

    /*
|--------------------------------------------------------------------------
| Invoice
|--------------------------------------------------------------------------
*/
Route::apiResource('invoices', InvoiceController::class);
Route::get('/invoices/datatables', [InvoiceController::class, 'datatables']);

// Update hanya amount_paid & payment_method
Route::put('/invoices/{id}/payment', [InvoiceController::class, 'updatePayment']);

// Catat pembayaran via Income (upload bukti, status pending)
Route::post('/invoices/{id}/record-payment', [InvoiceController::class, 'recordPayment']);

Route::get('/invoices/{id}/payment-status', [InvoiceController::class, 'getPaymentStatus']);
Route::post('/invoices/{id}/update-status', [InvoiceController::class, 'updateStatus']);
Route::get('/invoices/{invoiceId}/purchase-materials', [PurchaseMaterialController::class, 'getByInvoice']);
Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'cetakPdf'])->name('invoice.cetakPdf');


    /*
    |--------------------------------------------------------------------------
    | Purchase Materials
    |--------------------------------------------------------------------------
    */
    Route::apiResource('purchase-materials', PurchasematerialController::class);
    Route::get('/purchase-materials/{id}/service-category', [PurchasematerialController::class, 'getServiceCategory']);
    Route::get('/purchasematerials/datatables', [PurchasematerialController::class, 'datatables']);

    /*
    |--------------------------------------------------------------------------
    | Budget & Profit-Loss
    |--------------------------------------------------------------------------
    */
    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);
    Route::get('/budgets/{budget}', [BudgetController::class, 'show']);
    Route::put('/budgets/{budget}', [BudgetController::class, 'update']);
    Route::post('/budgets/{budget}/transactions', [BudgetController::class, 'addTransaction']);
    Route::post('/budget-transactions/{transaction}/approve', [BudgetController::class, 'approveTransaction']);
    Route::post('/budget-transactions/{transaction}/reject', [BudgetController::class, 'rejectTransaction']);

    Route::get('/profit-loss', [ProfitLossReportController::class, 'summary']);
    Route::get('/profit-loss/recap', [ProfitLossReportController::class, 'recap']);
    Route::get('/profit-loss-reports', [ProfitLossReportController::class, 'index']);
    Route::get('/profit-loss-reports/{report}', [ProfitLossReportController::class, 'show']);
    Route::post('/profit-loss-reports/generate', [ProfitLossReportController::class, 'generateReport']);
    Route::get('/profit-loss-reports/export/{format}', [ProfitLossReportController::class, 'export']);

    /*
    |--------------------------------------------------------------------------
    | Kasbon
    |--------------------------------------------------------------------------
    */
    Route::apiResource('kasbons', KasbonController::class)->except('create', 'edit');
    Route::get('/kasbons/export-pdf', [KasbonController::class, 'exportPdf']);
    Route::get('/kasbons/export-excel', [KasbonController::class, 'exportExcel']);
    Route::post('/kasbons/{kasbon}/upload-attachment', [KasbonController::class, 'uploadAttachment']);
    Route::delete('/kasbons/{kasbon}/attachments/{attachment}', [KasbonController::class, 'deleteAttachment']);
    Route::post('/kasbons/{kasbon}/approve', [KasbonController::class, 'approve']);
    Route::post('/kasbons/{kasbon}/reject', [KasbonController::class, 'reject']);
    Route::post('/kasbons/{kasbon}/payments', [KasbonController::class, 'addPayment']);
    Route::get('/kasbon-attachments/{attachment}/download', [KasbonController::class, 'downloadAttachment']);

    /*
    |--------------------------------------------------------------------------
    | Notifications & Activity Logs
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/activity-log', [ActivityController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Test & Debug Routes (remove in production)
|--------------------------------------------------------------------------
*/
Route::get('/test-log', function() {
    \Log::error('Test error log from API');
    return response()->json(['message' => 'Logged!']);
});
