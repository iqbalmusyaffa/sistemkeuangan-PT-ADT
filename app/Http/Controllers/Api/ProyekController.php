<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyek;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProyekController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Proyek::query();

            if ($request->filter === 'above_100m') {
                $query->above100Million();
            } elseif ($request->filter === 'below_100m') {
                $query->below100Million();
            }

            $proyeks = $query->withSum('expenses', 'amount')->get();

            // Tambahkan atribut total_expenses untuk frontend
            $proyeks->each(function ($proyek) {
                $proyek->total_expenses = $proyek->expenses_sum_amount;
            });

            return response()->json([
                'status' => 'success',
                'data' => $proyeks
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data proyek.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_customer' => 'required|string|max:255',
            'nama_proyek' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
            'alamat' => 'required|string|max:500',
            'no_telp' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:proyeks,email',
            'lokasi' => 'nullable|string|max:255',
            'anggaran_kontrak' => 'required|numeric|min:0',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status_project' => 'required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string',
        ]);

        try {
            $proyek = Proyek::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Proyek berhasil dibuat',
                'data' => $proyek,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat proyek.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
     public function show($id)
    {
        try {
            $proyek = Proyek::with('expenses')->findOrFail($id);
            // $proyek->total_expenses = $proyek->expenses()->sum('amount');
            $proyek->total_expenses = $proyek->expenses->sum('amount');

            $proyek->budget_percentage = $proyek->anggaran_kontrak > 0
                ? round(($proyek->total_expenses / $proyek->anggaran_kontrak) * 100, 2)
                : 0;

            return response()->json([
                'status' => 'success',
                'data' => $proyek
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyek tidak ditemukan.',
                'details' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data proyek.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, $id)
    {
        try {
            $proyek = Proyek::findOrFail($id);

            $validated = $request->validate([
                'nama_customer' => 'required|string|max:255',
                'nama_proyek' => 'required|string|max:255',
                'nama_perusahaan' => 'required|string|max:255',
                'alamat' => 'required|string|max:500',
                'no_telp' => 'required|string|max:20',
                'email' => 'nullable|email|max:255|unique:proyeks,email,' . $proyek->id,
                'lokasi' => 'nullable|string|max:255',
                'anggaran_kontrak' => 'required|numeric|min:0',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
                'status_project' => 'required|in:Berjalan,Selesai,Batal',
                'deskripsi' => 'nullable|string',
            ]);

            $totalExpenses = $proyek->expenses()->sum('amount');
            if ($validated['anggaran_kontrak'] < $totalExpenses) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nilai kontrak lebih kecil dari total pengeluaran proyek. Silakan periksa kembali.'
                ], 422);
            }

            $proyek->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Proyek berhasil diperbarui',
                'data' => $proyek,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyek tidak ditemukan.',
                'details' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui proyek.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
   public function destroy($id)
    {
        try {
            $proyek = Proyek::findOrFail($id);
            $proyek->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Proyek berhasil dihapus',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyek tidak ditemukan.',
                'details' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus proyek.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Project summary endpoint
     */
   public function summary($id)
{
    try {
        $proyek = Proyek::findOrFail($id);

        $totalIncome = \App\Models\Income::where('proyek_id', $id)
            ->where('status', 'Diterima')
            ->sum('jumlah');

        $totalExpenses = \App\Models\Expense::where('proyek_id', $id)
            ->where('status', 'Lunas')
            ->sum('amount');

        return response()->json([
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'anggaran_kontrak' => $proyek->anggaran_kontrak,
            'sisa_anggaran' => $proyek->anggaran_kontrak - $totalExpenses,
            'persentase_penggunaan' => $proyek->anggaran_kontrak > 0
                ? round(($totalExpenses / $proyek->anggaran_kontrak) * 100, 2)
                : 0,
            'profit_loss' => $totalIncome - $totalExpenses,
        ], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Proyek tidak ditemukan.',
            'details' => $e->getMessage(),
        ], 404);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil summary proyek.',
            'details' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Reduce project budget
 */
public function reduceBudget(Request $request, $id)
{
    $validated = $request->validate([
        'amount' => 'required|numeric|min:0',
    ]);

    $proyek = Proyek::findOrFail($id);

    if ($proyek->reduceBudget($validated['amount'])) {
        return response()->json(['message' => 'Budget reduced successfully'], 200);
    }

    return response()->json(['message' => 'Insufficient budget'], 400);
}

/**
 * Update progress proyek dan update status termin otomatis.
 */
  public function updateProgress(Request $request, $id)
    {
        $proyek = \App\Models\Proyek::with('termins')->findOrFail($id);
        $request->validate(['progress' => 'required|numeric|min:0|max:100']);

        $proyek->progress = $request->progress;
        $proyek->save();

        // Update status termin otomatis
        foreach ($proyek->termins as $termin) {
            if (
                $termin->status_termin === 'Belum Dibayar' &&
                $proyek->progress >= $termin->target_progress
            ) {
                // Ganti status menjadi 'Siap Bayar' atau sesuai alur kerja Anda
                $termin->status_termin = 'Siap Bayar';
                $termin->save();
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Progress proyek & status termin berhasil diupdate.']);
    }
    public function getInvoices($id)
{
    try {
        $invoices = \App\Models\Invoice::where('proyek_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $invoices
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil data invoice.',
            'details' => $e->getMessage()
        ], 500);
    }
}
public function getTermins($id)
{
    try {
        $proyek = Proyek::with('termins')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $proyek->termins
        ], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Proyek tidak ditemukan.',
            'details' => $e->getMessage()
        ], 404);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil data termin.',
            'details' => $e->getMessage()
        ], 500);
    }
}

}
