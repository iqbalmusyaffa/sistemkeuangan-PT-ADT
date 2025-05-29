<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Proyek;
use App\Models\Purchasematerial;
use App\Models\Termin;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Expense::with(['proyek', 'category', 'serviceCategory']);

            // Filter by project if provided
            if ($request->has('proyek_id')) {
                $query->byProyek($request->proyek_id);
            }

            // Filter by date range if provided
            if ($request->has(['start_date', 'end_date'])) {
                $query->byPeriod($request->start_date, $request->end_date);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            // Filter by source type if provided
            if ($request->has('source_type')) {
                $query->bySource($request->source_type);
            }

            $expenses = $query->orderBy('transaction_date', 'desc')->get();

            // Transform the data to include category name
            $expenses = $expenses->map(function ($expense) {
                $data = $expense->toArray();
                $data['category_name'] = $expense->category_name;
                return $data;
            });

            $response = [
                'status' => 'success',
                'data' => $expenses,
            ];

            // Calculate project summary if project_id is provided
            if ($request->has('proyek_id')) {
                try {
                    $proyek = Proyek::findOrFail($request->proyek_id);
                    $summary = $this->calculateProjectSummary($proyek);
                    $response['summary'] = $summary;
                } catch (\Exception $e) {
                    Log::error("Error calculating project summary: " . $e->getMessage());
                    // Continue without summary if there's an error
                }
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error("Error in ExpenseController@index: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data pengeluaran.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateProjectSummary($proyek)
    {
        $expenses = $proyek->expenses()
            ->select(
                DB::raw('SUM(CASE WHEN source_type = "termin" THEN amount ELSE 0 END) as total_termin'),
                DB::raw('SUM(CASE WHEN source_type = "purchase" THEN amount ELSE 0 END) as total_purchase'),
                DB::raw('SUM(amount) as total_expenses')
            )
            ->first();

        return [
            'total_termin' => $expenses->total_termin ?? 0,
            'total_purchase' => $expenses->total_purchase ?? 0,
            'total_expenses' => $expenses->total_expenses ?? 0,
            'project_name' => $proyek->nama_proyek
        ];
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'proyek_id' => 'required|exists:proyeks,id',
                'category_id' => 'required_without:service_category_id|exists:kategoris,id',
                'service_category_id' => 'required_without:category_id|exists:service_categories,id',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string',
                'transaction_date' => 'required|date',
                'status' => 'required|in:pending,approved,rejected,Lunas',
                'payment_method_id' => 'required|exists:payment_methods,id', // Changed from payment_method to payment_method_id
                'prepared_fund' => 'boolean',
                'source_type' => 'nullable|in:termin,purchase',
                'source_id' => 'nullable|integer'
            ]);

            DB::beginTransaction();

            // Validasi khusus jika source_type diisi
            if (!empty($validated['source_type']) && !empty($validated['source_id'])) {
                if ($validated['source_type'] === 'termin') {
                    $termin = \App\Models\Termin::find($validated['source_id']);
                    if (!$termin) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Termin tidak ditemukan'
                        ], 422);
                    }
                    if ($validated['amount'] > $termin->nilai_termin) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Jumlah pengeluaran tidak boleh melebihi nilai termin'
                        ], 422);
                    }
                } elseif ($validated['source_type'] === 'purchase') {
                    $purchase = \App\Models\Purchasematerial::find($validated['source_id']);
                    if (!$purchase) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Purchase material tidak ditemukan'
                        ], 422);
                    }
                    if ($validated['amount'] > $purchase->total_harga) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Jumlah pengeluaran tidak boleh melebihi total harga pembelian material'
                        ], 422);
                    }
                    // This check is good to prevent manual duplicate expense entries
                    $existingExpense = \App\Models\Expense::where('source_type', 'purchase')
                        ->where('source_id', $validated['source_id'])
                        ->first();

                    if ($existingExpense) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Pengeluaran untuk pembelian ini sudah tercatat.'
                        ], 422);
                    }
                }
            } else {
                // Validasi anggaran proyek (gunakan budget_adjusted jika ada)
                $proyek = \App\Models\Proyek::find($validated['proyek_id']);
                if ($proyek) {
                    $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
                    $totalExpenses = $proyek->expenses()->sum('amount');
                    $sisaAnggaran = $currentBudget - $totalExpenses;
                    if ($validated['amount'] > $sisaAnggaran) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Jumlah pengeluaran melebihi sisa anggaran proyek'
                        ], 422);
                    }
                }
            }

            $expense = Expense::create($validated);

            // Load necessary relations
            $expense->load(['proyek', 'category', 'serviceCategory']);
            $expense->category_name = $expense->category_name;

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil ditambahkan',
                'data' => $expense
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in ExpenseController@store: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menambah pengeluaran.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            Log::info('Attempting to fetch expense with ID: ' . $id);

            $expense = Expense::find($id);

            if (!$expense) {
                Log::warning('Expense not found with ID: ' . $id);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pengeluaran tidak ditemukan'
                ], 404);
            }

            // Load basic relationships
            $expense->load(['proyek', 'category', 'serviceCategory', 'invoice']); // Load invoice relation

            // Handle source relationship separately
            if (!empty($expense->source_type) && !empty($expense->source_id)) {
                try {
                    switch ($expense->source_type) {
                        case 'termin':
                            $expense->setRelation('source', Termin::find($expense->source_id));
                            break;
                        case 'purchase':
                            $expense->setRelation('source', Purchasematerial::find($expense->source_id));
                            break;
                    }
                } catch (\Exception $e) {
                    Log::warning('Error loading source relationship: ' . $e->getMessage());
                    $expense->setRelation('source', null);
                }
            }

            // Transform status to match frontend expectations
            $expense->status = ucfirst($expense->status);

            Log::info('Successfully fetched expense: ' . json_encode($expense->toArray()));

            return response()->json([
                'status' => 'success',
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ExpenseController@show: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data pengeluaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $expense = Expense::findOrFail($id);

            $validated = $request->validate([
                'proyek_id' => 'exists:proyeks,id',
                'category_id' => 'exists:kategoris,id',
                'service_category_id' => 'exists:service_categories,id',
                'amount' => 'numeric|min:0',
                'description' => 'string',
                'transaction_date' => 'date',
                'status' => 'in:pending,approved,rejected,Lunas',
                'payment_method_id' => 'exists:payment_methods,id', // Changed from payment_method to payment_method_id
                'prepared_fund' => 'boolean',
                'source_type' => 'nullable|in:termin,purchase',
                'source_id' => 'nullable|integer'
            ]);

            DB::beginTransaction();

            // Validasi khusus jika source_type diisi
            if (!empty($validated['source_type']) && !empty($validated['source_id'])) {
                if ($validated['source_type'] === 'termin') {
                    $termin = \App\Models\Termin::find($validated['source_id']);
                    if (!$termin) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Termin tidak ditemukan'
                        ], 422);
                    }
                    if ($validated['amount'] > $termin->nilai_termin) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Jumlah pengeluaran tidak boleh melebihi nilai termin'
                        ], 422);
                    }
                } elseif ($validated['source_type'] === 'purchase') {
                    $purchase = \App\Models\Purchasematerial::find($validated['source_id']);
                    if (!$purchase) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Purchase material tidak ditemukan'
                        ], 422);
                    }
                    if ($validated['amount'] > $purchase->total_harga) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Jumlah pengeluaran tidak boleh melebihi total harga pembelian material'
                        ], 422);
                    }
                }
            } else if (!empty($validated['proyek_id'])) {
                // Validasi anggaran proyek (gunakan budget_adjusted jika ada)
                $proyek = \App\Models\Proyek::find($validated['proyek_id']);
                if ($proyek) {
                    $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
                    // Exclude the current expense's amount from total expenses for validation
                    $totalExpenses = $proyek->expenses()->where('id', '!=', $expense->id)->sum('amount');
                    $sisaAnggaran = $currentBudget - $totalExpenses;
                    if ($validated['amount'] > $sisaAnggaran) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Jumlah pengeluaran melebihi sisa anggaran proyek'
                        ], 422);
                    }
                }
            }

            // Update data expense
            $expense->update($validated);

            // Load necessary relations
            $expense->load(['proyek', 'category', 'serviceCategory']);
            $expense->category_name = $expense->category_name;

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil diperbarui',
                'data' => $expense
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengeluaran tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in ExpenseController@update: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui pengeluaran.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $expense = Expense::findOrFail($id);

            // Check if expense is linked to a source type that should not be manually deleted
            if ($expense->source_type === Expense::SOURCE_PURCHASE || $expense->source_type === Expense::SOURCE_TERMIN || $expense->source_type === Expense::SOURCE_INVOICE) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus pengeluaran yang terkait dengan pembelian material, termin, atau invoice. Harap hapus dari sumbernya.'
                ], 403);
            }

            $expense->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil dihapus'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengeluaran tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error in ExpenseController@destroy: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus pengeluaran.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function datatables(Request $request)
    {
        $query = Expense::with(['proyek', 'category', 'serviceCategory']);
        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }
        // Add more filters as needed

        // Use Yajra DataTables if installed
        return \DataTables::of($query)->make(true);
    }

    protected function calculateProjectSummaries($proyekId)
    {
        $proyek = Proyek::findOrFail($proyekId);
        $expenses = Expense::where('proyek_id', $proyekId)->get();

        $totalPreparedFund = $expenses->sum('prepared_fund');
        $totalExpenses = $expenses->sum('amount');
        $remainingFund = $totalPreparedFund - $totalExpenses;

        // Group expenses by source type
        $purchaseExpenses = $expenses->where('source_type', 'purchase')->sum('amount');
        $terminExpenses = $expenses->where('source_type', 'termin')->sum('amount');
        $otherExpenses = $expenses->whereNull('source_type')->sum('amount');

        // Group expenses by category type
        $materialExpenses = $expenses->whereNotNull('category_id')->sum('amount');
        $serviceExpenses = $expenses->whereNotNull('service_category_id')->sum('amount');

        return [
            'total_prepared_fund' => $totalPreparedFund,
            'total_expenses' => $totalExpenses,
            'remaining_fund' => $remainingFund,
            'purchase_expenses' => $purchaseExpenses,
            'termin_expenses' => $terminExpenses,
            'other_expenses' => $otherExpenses,
            'material_expenses' => $materialExpenses,
            'service_expenses' => $serviceExpenses,
            'project_budget' => $proyek->anggaran_kontrak,
            'budget_percentage' => $proyek->anggaran_kontrak > 0
                ? ($totalExpenses / $proyek->anggaran_kontrak) * 100
                : 0
        ];
    }

    // This method is called by PurchaseMaterial's booted method, no need to call it manually from frontend
    // public function createFromPurchase(Purchasematerial $purchase)
    // {
    //     try {
    //         DB::beginTransaction();
    //
    //         // Validasi budget proyek sebelum create expense
    //         $proyek = $purchase->proyek;
    //         if ($proyek) {
    //             $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
    //             $totalExpenses = $proyek->expenses()->sum('amount');
    //             if (($totalExpenses + $purchase->total_harga) > $currentBudget) {
    //                 throw new \Exception('Total pengeluaran melebihi anggaran proyek.');
    //             }
    //         }
    //
    //         $expense = Expense::createFromPurchase($purchase); // This now returns the expense
    //
    //         DB::commit();
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Expense created from purchase',
    //             'data' => $expense // Return the expense data
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error creating expense from purchase: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create expense from purchase: ' . $e->getMessage() // Include error message
    //         ], 500);
    //     }
    // }

    // This method is called by Termin's booted method or similar logic
    // public function createFromTermin(Termin $termin)
    // {
    //     try {
    //         DB::beginTransaction();
    //
    //         // Validasi budget proyek sebelum create expense
    //         $proyek = $termin->proyek;
    //         if ($proyek) {
    //             $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
    //             $totalExpenses = $proyek->expenses()->sum('amount');
    //             if (($totalExpenses + $termin->nilai_termin) > $currentBudget) {
    //                 throw new \Exception('Total pengeluaran melebihi anggaran proyek.');
    //             }
    //         }
    //
    //         $expense = Expense::createFromTermin($termin); // This now returns the expense
    //
    //         DB::commit();
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Expense created from termin',
    //             'data' => $expense // Return the expense data
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error creating expense from termin: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create expense from termin: ' . $e->getMessage() // Include error message
    //         ], 500);
    //     }
    // }

    public function getProjectExpenseSummary($proyekId)
    {
        try {
            $summaries = $this->calculateProjectSummaries($proyekId);

            return response()->json([
                'success' => true,
                'message' => 'Project expense summary',
                'data' => $summaries
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting project expense summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get project expense summary'
            ], 500);
        }
    }

    /**
     * Create expense automatically when invoice is not paid
     * This method would typically be called by Invoice logic, not directly as an API endpoint usually
     */
    public function createFromInvoice($invoice)
    {
        try {
            DB::beginTransaction();

            // Validasi budget proyek sebelum create expense
            $proyek = $invoice->proyek;
            if ($proyek) {
                $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
                $totalExpenses = $proyek->expenses()->sum('amount');
                if (($totalExpenses + $invoice->total_amount) > $currentBudget) {
                    throw new \Exception('Total pengeluaran melebihi anggaran proyek.');
                }
            }

            // Check for existing expense for this invoice to prevent duplicates
            $existingExpense = Expense::where('source_type', Expense::SOURCE_INVOICE)
                                      ->where('source_id', $invoice->id)
                                      ->first();

            if ($existingExpense) {
                Log::info("Expense for Invoice ID {$invoice->id} already exists. Returning existing one.");
                DB::rollBack(); // Ensure no new transaction is committed if not needed
                return $existingExpense;
            }

            $expense = new Expense();
            $expense->proyek_id = $invoice->proyek_id;
            $expense->category_id = $invoice->kategori_id; // Ensure $invoice->kategori_id exists or is nullable
            $expense->amount = $invoice->total_amount;
            $expense->description = "Tagihan invoice " . ($invoice->invoice_number ?? $invoice->id);
            $expense->transaction_date = now();
            $expense->status = 'pending'; // Invoices often create pending expenses
            $expense->payment_method_id = $invoice->payment_method_id; // Use payment_method_id
            $expense->source_type = Expense::SOURCE_INVOICE;
            $expense->source_id = $invoice->id;
            $expense->invoice_id = $invoice->id;
            $expense->user_id = auth()->id(); // Assign current authenticated user

            $expense->save();

            DB::commit();

            return $expense;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating expense from invoice: ' . $e->getMessage());
            throw $e;
        }
    }
}
