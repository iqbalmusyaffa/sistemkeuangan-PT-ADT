<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $incomes = Expense::with(['user', 'company', 'category'])->latest()->get();
            return response()->json([
                'success' => true,
                'message' => 'List data pemasukan',
                'data' => $incomes
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            \Log::info('Income store request: ' . json_encode($request->all()));

            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'category_id' => 'required|exists:kategoris,id',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'transaction_date' => 'required|date',
                'status' => 'required|boolean', // status dikirim dalam bentuk true/false
            ]);

            // Generate kode transaksi
            $today = now()->format('d.m');
            $lastIncome = \App\Models\Expense::latest()->first();
            $lastId = $lastIncome ? $lastIncome->id + 1 : 1;
            $kodeTransaksi = $today . '.' . str_pad($lastId, 3, '0', STR_PAD_LEFT);

            // Convert boolean to enum string
            $status = $request->status ? 'Lunas' : 'Pending';

            $income = \App\Models\Expense::create([
                'user_id' => auth()->id(),
                'company_id' => $validated['company_id'],
                'category_id' => $validated['category_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'status' => $status,
                'kode_transaksi' => $kodeTransaksi,
            ]);

            return response()->json([
                'message' => 'Income created successfully',
                'data' => $income
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Income store exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'message' => 'Failed to create income',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $income = Expense::with(['user', 'company', 'category'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Detail pemasukan ditemukan',
                'data' => $income
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan: ' . $e->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            \Log::info('Income update request: ' . json_encode($request->all()));

            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'category_id' => 'required|exists:kategoris,id',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'transaction_date' => 'required|date',
                'status' => 'required|boolean',
            ]);

            $income = \App\Models\Expense::findOrFail($id);

            $income->update([
                'company_id' => $validated['company_id'],
                'category_id' => $validated['category_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'status' => $request->status ? 'Lunas' : 'Pending',
            ]);

            return response()->json([
                'message' => 'Income updated successfully',
                'data' => $income
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Income update exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'message' => 'Failed to update income',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $income = Expense::findOrFail($id);
            $income->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pemasukan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }
}
