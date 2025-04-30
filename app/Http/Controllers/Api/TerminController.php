<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Termin;
use App\Models\Proyek;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TerminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Termin::with('proyek')->orderBy('created_at', 'desc');
        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }
        $termins = $query->get();
        return response()->json($termins);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'proyek_id' => 'required|exists:proyeks,id',
            'invoice_id' => 'required|exists:invoices,id',
            'nama_termin' => 'required|string|max:255',
            'nilai_termin' => 'required|numeric|min:0',
            'dp_percentage' => 'required|numeric|min:0|max:100',
            'nilai_dp' => 'required|numeric|min:0',
            'nilai_pelunasan' => 'required|numeric|min:0',
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date',
            'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
            'keterangan' => 'nullable|string',
        ]);

        // Validasi invoice milik proyek yang sama
        $invoice = \App\Models\Invoice::where('id', $validated['invoice_id'])
            ->where('proyek_id', $validated['proyek_id'])
            ->first();
        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak valid untuk proyek ini'], 422);
        }

        // Validasi anggaran proyek
        $proyek = \App\Models\Proyek::find($validated['proyek_id']);
        $totalTermin = \App\Models\Termin::where('proyek_id', $validated['proyek_id'])->sum('nilai_termin');
        if ($proyek && ($totalTermin + $validated['nilai_termin']) > $proyek->anggaran_kontrak) {
            return response()->json(['message' => 'Total termin melebihi anggaran proyek'], 422);
        }

        // Nilai termin tidak boleh melebihi total invoice
        if ($validated['nilai_termin'] > $invoice->total_amount) {
            return response()->json(['message' => 'Nilai termin tidak boleh melebihi total invoice'], 422);
        }

        // Nilai DP tidak boleh lebih besar dari nilai termin
        if ($validated['nilai_dp'] > $validated['nilai_termin']) {
            return response()->json(['message' => 'Nilai DP tidak boleh lebih besar dari nilai termin'], 422);
        }

        // Nilai pelunasan harus sesuai
        if ($validated['nilai_pelunasan'] != ($validated['nilai_termin'] - $validated['nilai_dp'])) {
            return response()->json(['message' => 'Nilai pelunasan harus sama dengan nilai termin dikurangi nilai DP'], 422);
        }

        // Calculate DP and Pelunasan values if not provided
        if (!isset($validated['nilai_dp']) || $validated['nilai_dp'] == 0) {
            $validated['nilai_dp'] = $validated['nilai_termin'] * ($validated['dp_percentage'] / 100);
        }

        if (!isset($validated['nilai_pelunasan']) || $validated['nilai_pelunasan'] == 0) {
            $validated['nilai_pelunasan'] = $validated['nilai_termin'] - $validated['nilai_dp'];
        }

        $termin = Termin::create($validated);

        return response()->json([
            'message' => 'Termin berhasil dibuat',
            'data' => $termin,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Termin $termin)
    {
        $termin->load('proyek');
        return response()->json($termin);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Termin $termin)
    {
        $validated = $request->validate([
            'proyek_id' => 'required|exists:proyeks,id',
            'nama_termin' => 'required|string|max:255',
            'nilai_termin' => 'required|numeric|min:0',
            'dp_percentage' => 'required|numeric|min:0|max:100',
            'nilai_dp' => 'required|numeric|min:0',
            'nilai_pelunasan' => 'required|numeric|min:0',
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date',
            'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
            'keterangan' => 'nullable|string',
        ]);

        // Calculate DP and Pelunasan values if not provided
        if (!isset($validated['nilai_dp']) || $validated['nilai_dp'] == 0) {
            $validated['nilai_dp'] = $validated['nilai_termin'] * ($validated['dp_percentage'] / 100);
        }

        if (!isset($validated['nilai_pelunasan']) || $validated['nilai_pelunasan'] == 0) {
            $validated['nilai_pelunasan'] = $validated['nilai_termin'] - $validated['nilai_dp'];
        }

        $termin->update($validated);
        // Tambahkan update status invoice
        if ($termin->invoice_id) {
            $termin->invoice->updateStatusFromTermins();
        }

        return response()->json([
            'message' => 'Termin berhasil diupdate',
            'data' => $termin,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Termin $termin)
    {
        $invoice = $termin->invoice;
        $termin->delete();
        // Tambahkan update status invoice
        if ($invoice) {
            $invoice->updateStatusFromTermins();
        }
        return response()->json([
            'message' => 'Termin berhasil dihapus',
        ]);
    }

    /**
     * Get termins by project ID.
     */
    public function getByProject($proyekId)
    {
        $termins = Termin::where('proyek_id', $proyekId)
            ->with('proyek')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $termins,
            'project' => Proyek::find($proyekId)
        ]);
    }

    /**
     * Update termin status.
     */
    public function updateStatus(Request $request, Termin $termin)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validate([
                'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
                'tanggal_dp' => 'nullable|date',
                'tanggal_pelunasan' => 'nullable|date'
            ]);

            $oldStatus = $termin->status_termin;
            $newStatus = $validatedData['status_termin'];

            // Update termin status and dates
            $termin->status_termin = $newStatus;
            if ($newStatus === 'DP Dibayar' && !$termin->tanggal_dp) {
                $termin->tanggal_dp = $validatedData['tanggal_dp'] ?? now();
            }
            if ($newStatus === 'Lunas' && !$termin->tanggal_pelunasan) {
                $termin->tanggal_pelunasan = $validatedData['tanggal_pelunasan'] ?? now();
            }
            $termin->save();

            // Update status invoice otomatis setelah update termin
            if ($termin->invoice_id) {
                $invoice = $termin->invoice;
                $invoice->updateStatusFromTermins();
            }

            // Create expense records based on status changes
            if ($oldStatus !== $newStatus) {
                if ($newStatus === 'DP Dibayar') {
                    // Create expense for DP
                    $expense = new \App\Models\Expense([
                        'user_id' => auth()->id(),
                        'proyek_id' => $termin->proyek_id,
                        'category_id' => null, // You might want to set a specific category for DP payments
                        'amount' => $termin->nilai_dp,
                        'description' => "Pembayaran DP Termin " . $termin->nama_termin,
                        'transaction_date' => $termin->tanggal_dp,
                        'status' => 'Lunas',
                        'payment_method' => null,
                        'prepared_fund' => $termin->nilai_dp,
                        'source_type' => 'termin',
                        'source_id' => $termin->id
                    ]);
                    $expense->save();
                    $termin->expense_id = $expense->id;
                    $termin->save();
                } elseif ($newStatus === 'Lunas') {
                    // Create expense for final payment
                    $expense = new \App\Models\Expense([
                        'user_id' => auth()->id(),
                        'proyek_id' => $termin->proyek_id,
                        'category_id' => null, // You might want to set a specific category for final payments
                        'amount' => $termin->nilai_termin - $termin->nilai_dp,
                        'description' => "Pelunasan Termin " . $termin->nama_termin,
                        'transaction_date' => $termin->tanggal_pelunasan,
                        'status' => 'Lunas',
                        'payment_method' => null,
                        'prepared_fund' => $termin->nilai_termin - $termin->nilai_dp,
                        'source_type' => 'termin',
                        'source_id' => $termin->id
                    ]);
                    $expense->save();
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Status termin berhasil diupdate',
                'data' => $termin
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating termin status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengupdate status termin',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
