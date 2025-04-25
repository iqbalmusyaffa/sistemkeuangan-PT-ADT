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
                'status' => 'required|in:pending,approved,rejected',
                'payment_method' => 'required|string',
                'prepared_fund' => 'boolean',
                'source_type' => 'nullable|in:termin,purchase',
                'source_id' => 'nullable|integer'
            ]);

            DB::beginTransaction();

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
            
            // First, get the expense without any relationships
            $expense = Expense::find($id);

            if (!$expense) {
                Log::warning('Expense not found with ID: ' . $id);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pengeluaran tidak ditemukan'
                ], 404);
            }

            // Load basic relationships
            $expense->load(['proyek', 'category', 'serviceCategory']);

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
                'status' => 'in:pending,approved,rejected',
                'payment_method' => 'string',
                'prepared_fund' => 'boolean',
                'source_type' => 'nullable|in:termin,purchase',
                'source_id' => 'nullable|integer'
            ]);

            DB::beginTransaction();

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
            
            if ($expense->source_type) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus pengeluaran yang terkait dengan termin atau pembelian'
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

    public function createFromPurchase(Purchasematerial $purchase)
    {
        try {
            DB::beginTransaction();

            $expense = Expense::createFromPurchase($purchase);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Expense created from purchase',
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating expense from purchase: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense from purchase'
            ], 500);
        }
    }

    public function createFromTermin(Termin $termin)
    {
        try {
            DB::beginTransaction();

            $expense = Expense::createFromTermin($termin);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Expense created from termin',
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating expense from termin: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense from termin'
            ], 500);
        }
    }

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
}
