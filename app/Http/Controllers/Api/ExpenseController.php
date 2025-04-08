<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index()
    {
        try {
            $expenses = Expense::with(['user', 'company', 'category'])->latest()->get();
            return response()->json([
                'success' => true,
                'message' => 'List data pengeluaran',
                'data' => $expenses
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('Expense store request: ' . json_encode($request->all()));

            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'category_id' => 'required|exists:kategoris,id',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'transaction_date' => 'required|date',
                'status' => 'required|boolean',
                'kode_transaksi' => 'nullable|string|max:255|unique:expenses,kode_transaksi',
            ]);

            $kodeTransaksi = $validated['kode_transaksi'] ?? null;

            if (!$kodeTransaksi) {
                $today = now()->format('d.m');
                $lastExpense = Expense::latest()->first();
                $lastId = $lastExpense ? $lastExpense->id + 1 : 1;
                $kodeTransaksi = $today . '.' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
            }

            $status = $validated['status'] ? 'Lunas' : 'Pending';

            $expense = Expense::create([
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
                'message' => 'Expense created successfully',
                'data' => $expense
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Expense store exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'message' => 'Failed to create expense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $expense = Expense::with(['user', 'company', 'category'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Detail pengeluaran ditemukan',
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan: ' . $e->getMessage()], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $expense = Expense::findOrFail($id);

            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'category_id' => 'required|exists:kategoris,id',
                'amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'transaction_date' => 'required|date',
                'status' => 'required|boolean',
                'kode_transaksi' => 'nullable|string|max:255|unique:expenses,kode_transaksi,' . $id,
            ]);

            $kodeTransaksi = $validated['kode_transaksi'] ?? $expense->kode_transaksi;

            if (!$kodeTransaksi) {
                $today = now()->format('d.m');
                $kodeTransaksi = $today . '.' . str_pad($expense->id, 3, '0', STR_PAD_LEFT);
            }

            $status = $validated['status'] ? 'Lunas' : 'Pending';

            $expense->update([
                'company_id' => $validated['company_id'],
                'category_id' => $validated['category_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'status' => $status,
                'kode_transaksi' => $kodeTransaksi,
            ]);

            return response()->json([
                'message' => 'Expense updated successfully',
                'data' => $expense
            ]);
        } catch (\Throwable $e) {
            \Log::error('Expense update exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'message' => 'Failed to update expense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $expense = Expense::findOrFail($id);
            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }
}
