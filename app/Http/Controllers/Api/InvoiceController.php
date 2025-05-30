<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\PurchaseMaterial;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\InvoiceResource;
use App\Models\Proyek;
use App\Models\Income;
use App\Models\PaymentMethod; // Added PaymentMethod import

class InvoiceController extends Controller
{
    // Menampilkan daftar invoice
    public function index(Request $request)
    {
        $query = Invoice::with([
            'proyek',
            'purchaseMaterials.category',
            'purchaseMaterials.serviceCategory',
            'purchaseMaterials.unit',
            'purchaseMaterials.merek',
            'purchaseMaterials.expense',
            'termins',
            'expenses',
            'paymentMethod'
        ]);
        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }
        $invoices = $query->get();
        return InvoiceResource::collection($invoices);
    }

    // Menampilkan detail invoice
    public function show($id)
    {
        $invoice = Invoice::with([
            'proyek',
            'purchaseMaterials.category',
            'purchaseMaterials.serviceCategory',
            'purchaseMaterials.unit',
            'purchaseMaterials.merek',
            'purchaseMaterials.expense',
            'termins',
            'expenses',
            'paymentMethod'
        ])->findOrFail($id);
        return new InvoiceResource($invoice);
    }

    public function store(Request $request)
    {
        try {
            \Log::info('Incoming request data for creating invoice:', $request->all());

            $validated = $request->validate([
                'proyek_id' => 'required|exists:proyeks,id',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'invoice_date' => 'required|date',
                'purchase_materials' => 'required|array',
                'purchase_materials.*.item' => 'required|string',
                'purchase_materials.*.type' => 'required|string',
                'purchase_materials.*.spesifikasi' => 'nullable|string', // <-- ADDED THIS LINE
                'purchase_materials.*.deskripsi' => 'nullable|string',   // <-- ADDED THIS LINE
                'purchase_materials.*.qty' => 'required|numeric|min:0',
                'purchase_materials.*.harga' => 'required|numeric|min:0',
                'purchase_materials.*.unit_id' => 'required|exists:units,id',
                'purchase_materials.*.category_id' => 'nullable|exists:kategoris,id',
                'purchase_materials.*.service_category_id' => 'nullable|exists:service_categories,id',
                'purchase_materials.*.merek_id' => 'nullable|exists:mereks,id',
                'use_ppn' => 'boolean',
                'use_pph_non_final' => 'boolean',
                'use_pph_final' => 'boolean',
                'notes' => 'nullable|string',

                // Direct expenses associated with invoice
                'expenses' => 'nullable|array',
                'expenses.*.description' => 'required_with:expenses|string',
                'expenses.*.amount' => 'required_with:expenses|numeric|min:0',
                'expenses.*.category_id' => 'required_with:expenses|exists:kategoris,id',
                'expenses.*.service_category_id' => 'nullable|exists:service_categories,id',
                'expenses.*.payment_method_id' => 'required_with:expenses|exists:payment_methods,id', // Added validation for payment_method_id
            ]);

            \Log::info('Validated invoice data:', $validated);
            \Log::info('Data purchase_materials diterima:', $validated['purchase_materials']);

            // Validate qty/harga
            foreach ($validated['purchase_materials'] as $idx => $mat) {
                if (!is_numeric($mat['qty']) || !is_numeric($mat['harga']) || $mat['qty'] <= 0 || $mat['harga'] <= 0) {
                    \Log::warning('Invalid qty/harga in purchase_materials', ['index' => $idx, 'item' => $mat]);
                    throw new \Exception('Qty dan harga pada item ke-' . ($idx + 1) . ' harus lebih dari 0 dan valid.');
                }
            }

            DB::beginTransaction();

            $lastInvoice = Invoice::latest()->first();
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(($lastInvoice ? $lastInvoice->id + 1 : 1), 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'proyek_id' => $validated['proyek_id'],
                'payment_method_id' => $validated['payment_method_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validated['invoice_date'],
                'total_amount' => 0, // Will be updated by PurchaseMaterial observers
                'use_ppn' => $validated['use_ppn'] ?? false,
                'use_pph_non_final' => $validated['use_pph_non_final'] ?? false,
                'use_pph_final' => $validated['use_pph_final'] ?? false,
                'notes' => $validated['notes'] ?? null,
                'status' => 'unpaid',
                'amount_paid' => 0,
                'profit_margin_percentage' => 30.00, // Default profit margin
            ]);

            // Insert purchase materials
            $purchaseMaterialsToInsert = [];
            foreach ($validated['purchase_materials'] as $material) {
                $unit = \App\Models\Unit::find($material['unit_id']);
                $isService = $unit && in_array(strtolower($unit->unit_name), ['jasa', 'set', 'transaksi']);

                if ($isService && empty($material['service_category_id'])) {
                    throw new \Exception('Service category is required for service items');
                }
                if (!$isService && (empty($material['category_id']) || empty($material['merek_id']))) {
                    throw new \Exception('Category and Brand are required for non-service items');
                }

                $totalHarga = $material['qty'] * $material['harga'];
                $purchaseMaterialsToInsert[] = [
                    'invoice_id' => $invoice->id,
                    'proyek_id' => $invoice->proyek_id,
                    'item' => $material['item'],
                    'type' => $material['type'],
                    'qty' => $material['qty'],
                    'harga' => $material['harga'],
                    'unit_id' => $material['unit_id'],
                    'category_id' => $material['category_id'] ?? null,
                    'service_category_id' => $material['service_category_id'] ?? null,
                    'merek_id' => $material['merek_id'] ?? null,
                    'spesifikasi' => $material['spesifikasi'] ?? null,
                    'deskripsi' => $material['deskripsi'] ?? null,
                    'total_harga' => $totalHarga,
                    'is_service' => $isService,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($purchaseMaterialsToInsert, 100) as $chunk) {
                // Use `create` instead of `insert` to trigger model events (like `saved` for expense creation)
                foreach ($chunk as $data) {
                    PurchaseMaterial::create($data);
                }
            }

            // Insert additional direct expenses if provided
            if (!empty($validated['expenses'])) {
                \Log::info('Memulai proses penyimpanan additional expenses for invoice ID: ' . $invoice->id);

                $additionalExpenses = collect($validated['expenses'])->map(function ($expense) use ($invoice) {
                    return [
                        'proyek_id' => $invoice->proyek_id,
                        'invoice_id' => $invoice->id,
                        'description' => $expense['description'],
                        'amount' => $expense['amount'],
                        'category_id' => $expense['category_id'] ?? null,
                        'service_category_id' => $expense['service_category_id'] ?? null,
                        'transaction_date' => now(),
                        'status' => Expense::STATUS_PENDING,
                        'payment_method_id' => $expense['payment_method_id'], // Assign payment_method_id here
                        'user_id' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                foreach (array_chunk($additionalExpenses, 100) as $chunk) {
                    Expense::insert($chunk);
                }
                \Log::info('Additional expenses successfully saved for invoice ID: ' . $invoice->id, $additionalExpenses);
            } else {
                \Log::info('No additional expenses to save for invoice ID: ' . $invoice->id);
            }

            $invoice->refresh(); // Refresh to get the latest total_amount and other derived values from observers
            $invoice->calculateAllFinancialValues(); // Recalculate all financial values based on updated data

            DB::commit();

            \Log::info('Invoice created successfully:', ['invoice_id' => $invoice->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice created successfully',
                'data' => new InvoiceResource($invoice)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating invoice:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while creating the invoice: ' . $e->getMessage(),
            ], 500);
        }
    }


    // Mengupdate invoice (status dan pembayaran)
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:unpaid,partially_paid,paid,cancelled',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id', // Make payment_method_id nullable for updates if it's not always provided
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            DB::beginTransaction();
            $invoice = Invoice::findOrFail($id);

            // Update invoice payment and status
            $invoice->amount_paid = $request->amount_paid;
            $invoice->status = $invoice->determineStatus(); // Re-determine status based on new amount paid
            if ($request->has('payment_method_id')) {
                $invoice->payment_method_id = $request->payment_method_id;
            }
            $invoice->save();

            // After updating the payment, recalculate all financial values
            $invoice->calculateAllFinancialValues();

            // Update statuses of associated expenses based on the new invoice status
            $newExpenseStatus = Expense::STATUS_PENDING;
            if ($invoice->status === Invoice::STATUS_PAID) {
                $newExpenseStatus = Expense::STATUS_LUNAS;
            } else if ($invoice->status === Invoice::STATUS_PARTIALLY_PAID) {
                $newExpenseStatus = Expense::STATUS_PENDING;
            } else if ($invoice->status === Invoice::STATUS_UNPAID) {
                $newExpenseStatus = Expense::STATUS_PENDING;
            }


            // Update expenses linked via purchase materials
            $invoice->purchaseMaterials->each(function ($purchaseMaterial) use ($newExpenseStatus, $invoice) {
                if ($purchaseMaterial->expense) {
                    $purchaseMaterial->expense->update([
                        'status' => $newExpenseStatus,
                        'payment_method_id' => $invoice->payment_method_id // Update expense payment_method_id from invoice
                    ]);
                }
            });

            // Update direct expenses linked to this invoice
            $invoice->expenses->each(function ($expense) use ($newExpenseStatus, $invoice) {
                $expense->update([
                    'status' => $newExpenseStatus,
                    'payment_method_id' => $invoice->payment_method_id // Update expense payment_method_id from invoice
                ]);
            });

            DB::commit();

            return new InvoiceResource($invoice->fresh()); // Return fresh instance to include updated relationships
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating invoice: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update invoice. ' . $e->getMessage(),
            ], 500);
        }
    }

    // Menghapus invoice
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'unpaid') {
            return response()->json(['error' => 'Invoice cannot be deleted unless it is unpaid.'], 400);
        }

        try {
            DB::beginTransaction();

            // Expenses associated directly with this invoice (not via purchase materials/termins)
            $invoice->expenses()->delete();

            // Termins are deleted first
            $invoice->termins()->delete();

            // Purchase materials are deleted. Their `deleted` observer will also delete associated expenses.
            $invoice->purchaseMaterials()->delete();

            // Finally delete the invoice
            $invoice->delete();

            DB::commit();
            return response()->json(['message' => 'Invoice and all associated records deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete invoice. ' . $e->getMessage()], 500);
        }
    }

    // Record payment for an invoice (this method might be better placed in Invoice model or a dedicated service)
    public function recordPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id', // payment_method_id is required for a new payment record
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $invoice = Invoice::findOrFail($id);

            // Add the payment amount to the existing amount_paid
            $newAmountPaid = $invoice->amount_paid + $request->amount;
            $invoice->updateAmountPaid($newAmountPaid); // This method also updates status and saves

            // After updating the payment, recalculate all financial values
            $invoice->calculateAllFinancialValues();

            // Update statuses of associated expenses based on the new invoice status
            $newExpenseStatus = Expense::STATUS_PENDING;
            if ($invoice->status === Invoice::STATUS_PAID) {
                $newExpenseStatus = Expense::STATUS_LUNAS;
            } else if ($invoice->status === Invoice::STATUS_PARTIALLY_PAID) {
                $newExpenseStatus = Expense::STATUS_PENDING;
            } else if ($invoice->status === Invoice::STATUS_UNPAID) {
                $newExpenseStatus = Expense::STATUS_PENDING;
            }

            // Update expenses linked via purchase materials
            $invoice->purchaseMaterials->each(function ($purchaseMaterial) use ($newExpenseStatus, $request) {
                if ($purchaseMaterial->expense) {
                    $purchaseMaterial->expense->update([
                        'status' => $newExpenseStatus,
                        'payment_method_id' => $request->payment_method_id // Update expense payment_method_id from payment record
                    ]);
                }
            });

            // Update direct expenses linked to this invoice
            $invoice->expenses->each(function ($expense) use ($newExpenseStatus, $request) {
                $expense->update([
                    'status' => $newExpenseStatus,
                    'payment_method_id' => $request->payment_method_id // Update expense payment_method_id from payment record
                ]);
            });


            // Create an income record for this payment
            $income = Income::create([
                'user_id' => auth()->id(), // Assuming authenticated user
                'proyek_id' => $invoice->proyek_id,
                'invoice_id' => $invoice->id,
                'jumlah' => $request->amount,
                'tanggal_diterima' => now()->toDateString(),
                'metode_pembayaran_id' => $request->payment_method_id,
                'deskripsi' => "Pembayaran invoice #{$invoice->invoice_number}",
                'status' => 'Diterima',
                'type' => 'invoice_payment', // Custom type for invoice payments
            ]);

            DB::commit();

            // Reload invoice to get updated relations and calculated values
            $invoice->load([
                'proyek',
                'purchaseMaterials.category',
                'purchaseMaterials.serviceCategory',
                'purchaseMaterials.unit',
                'purchaseMaterials.merek',
                'purchaseMaterials.expense',
                'termins',
                'expenses',
                'paymentMethod'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment recorded successfully',
                'data' => [
                    'invoice' => new InvoiceResource($invoice),
                    'income' => $income
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }


    // Get project financial summary
    public function getProjectFinancialSummary($projectId)
    {
        try {
            $invoices = Invoice::where('proyek_id', $projectId)->get();

            if ($invoices->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No invoices found for this project'
                ], 404);
            }

            // These accessors on Invoice model should calculate correctly
            $totalIncome = $invoices->sum(fn($i) => $i->total_project_income);
            $totalExpenses = $invoices->sum(fn($i) => $i->total_project_expenses);
            $totalProfitLoss = $invoices->sum(fn($i) => $i->project_profit_loss);
            $totalProfitLossPercentage = $totalExpenses > 0 ? ($totalProfitLoss / $totalExpenses) * 100 : 0;

            $paymentSummary = [
                'total_invoice_amount' => $invoices->sum('total_amount'),
                'total_paid_amount' => $invoices->sum('amount_paid'),
                'total_remaining' => $invoices->sum('total_amount') - $invoices->sum('amount_paid'),
                'total_termin_amount' => $invoices->sum(function($invoice) {
                    return $invoice->termins->sum('nilai_termin');
                }),
                'total_paid_termin' => $invoices->sum(function($invoice) {
                    return $invoice->termins->where('status_termin', 'Lunas')->sum('nilai_termin');
                }),
                'total_dp_paid' => $invoices->sum(function($invoice) {
                    return $invoice->termins->where('status_termin', 'DP Dibayar')->sum('nilai_dp');
                })
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_income' => $totalIncome,
                    'total_expenses' => $totalExpenses,
                    'total_profit_loss' => $totalProfitLoss,
                    'total_profit_loss_percentage' => $totalProfitLossPercentage,
                    'payment_summary' => $paymentSummary,
                    'invoices' => InvoiceResource::collection($invoices)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get project financial summary: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get payment status for an invoice
    public function getPaymentStatus($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            // Assuming getPaymentStatusSummary is a method on the Invoice model that calculates this.
            // If not, you'll need to define it or calculate it here.
            $paymentStatus = [
                'status' => $invoice->status,
                'total_amount' => (float) $invoice->total_amount,
                'amount_paid' => (float) $invoice->amount_paid,
                'remaining_payment' => (float) $invoice->total_amount - (float) $invoice->amount_paid,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $paymentStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get payment status: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Generate and download PDF for an invoice
     */
    public function cetakPdf($id)
    {
        try {
            $invoice = Invoice::with([
                'proyek',
                'purchaseMaterials',
                'paymentMethod',
            ])->findOrFail($id);

            $pdf = \PDF::loadView('invoices.pdf', [
                'invoice' => $invoice
            ]);
            $pdf->setPaper('a4', 'portrait');

            $filename = 'invoice_' . $invoice->invoice_number . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error generating invoice PDF: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}
