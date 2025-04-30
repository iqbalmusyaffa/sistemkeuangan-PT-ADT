<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Income;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $incomes = Income::with(['kategori', 'paymentMethod', 'proyek', 'createdBy', 'updatedBy'])
                ->latest()
                ->get();
            
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
                'kategori_id' => 'required|exists:kategoris,id',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'proyek_id' => 'nullable|exists:proyeks,id',
                'jumlah' => 'required|numeric|min:0',
                'deskripsi' => 'nullable|string',
                'tanggal' => 'required|date',
                'status' => 'required|in:Pending,Diterima,Ditolak',
                'kode_transaksi' => 'nullable|string|max:255|unique:incomes,kode_transaksi',
                'bukti_pembayaran' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            $income = new Income();
            $income->fill($validated);
            $income->created_by = Auth::id();
            $income->updated_by = Auth::id();

            if ($request->hasFile('bukti_pembayaran')) {
                $file = $request->file('bukti_pembayaran');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bukti_pembayaran', $filename, 'public');
                $income->bukti_pembayaran = $path;
            }

            $income->save();

            return response()->json([
                'success' => true,
                'message' => 'Pemasukan berhasil ditambahkan',
                'data' => $income
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Income store exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pemasukan',
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
            $income = Income::with(['kategori', 'paymentMethod', 'proyek', 'createdBy', 'updatedBy'])
                ->findOrFail($id);
            
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
            $income = Income::findOrFail($id);

            $validated = $request->validate([
                'kategori_id' => 'required|exists:kategoris,id',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'proyek_id' => 'nullable|exists:proyeks,id',
                'jumlah' => 'required|numeric|min:0',
                'deskripsi' => 'nullable|string',
                'tanggal' => 'required|date',
                'status' => 'required|in:Pending,Diterima,Ditolak',
                'kode_transaksi' => 'nullable|string|max:255|unique:incomes,kode_transaksi,' . $id,
                'bukti_pembayaran' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            $income->fill($validated);
            $income->updated_by = Auth::id();

            if ($request->hasFile('bukti_pembayaran')) {
                // Delete old file if exists
                if ($income->bukti_pembayaran) {
                    Storage::disk('public')->delete($income->bukti_pembayaran);
                }

                $file = $request->file('bukti_pembayaran');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bukti_pembayaran', $filename, 'public');
                $income->bukti_pembayaran = $path;
            }

            $income->save();

            return response()->json([
                'success' => true,
                'message' => 'Pemasukan berhasil diperbarui',
                'data' => $income
            ]);
        } catch (\Throwable $e) {
            \Log::error('Income update exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pemasukan',
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
            $income = Income::findOrFail($id);

            // Delete bukti pembayaran file if exists
            if ($income->bukti_pembayaran) {
                Storage::disk('public')->delete($income->bukti_pembayaran);
            }

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
