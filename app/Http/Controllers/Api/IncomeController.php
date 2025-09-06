<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Income, Termin, Invoice, Proyek, Expense};
use App\Http\Resources\IncomeResource;
use Illuminate\Support\Facades\{Auth, Storage, DB, Log};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IncomeController extends Controller
{
    /* =============================================================== */
    /*  HELPER METHODS                                                 */
    /* =============================================================== */
protected function applyTerminToIncome(?Termin $termin, Income $income, array $validated): void
{
    if ($termin) {
        // Proteksi: termin tidak boleh diubah kalau sudah di-approve
        if (strtolower($termin->status_approval) === 'approved') {
            abort(response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghubungkan pemasukan ke termin yang sudah disetujui.'
            ], 403));
        }

        // Ambil jenis termin
        $jenis = strtoupper($termin->jenis_termin);

        // Isi ulang ke income berdasarkan data termin
        $income->termin_id  = $termin->id;
        $income->type       = $jenis === 'DP' ? 'dp' : ($jenis === 'PELUNASAN' ? 'pelunasan' : null);
        $income->jumlah     = $jenis === 'DP'
            ? ($termin->nilai_dp ?? 0)
            : ($jenis === 'PELUNASAN'
                ? ($termin->nilai_pelunasan ?? 0)
                : ($termin->nilai_termin ?? 0));
        $income->invoice_id = $validated['invoice_id'] ?? $termin->invoice_id;
    } else {
        // Jika income berdiri sendiri, tidak pakai termin
        $income->termin_id  = null;
        $income->invoice_id = $validated['invoice_id'] ?? null;
        $income->type       = $validated['type'] ?? null;
        $income->jumlah     = $validated['jumlah'];
    }
}

    /** Generate unique kode like INC-20250708-ABCDE */
    private function generateKodeTransaksi(): string
    {
        return 'INC-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }

    /** Generate unique kode termin like TRM-20250708-ABCD */
    private function generateKodeTermin(): string
    {
        return 'TRM-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
    }

    /** Guard to ensure proyek budget still sufficient */
    private function assertBudgetIsEnough(Income $income): void
    {
        if (!$income->proyek_id) return; // generic income, skip

        $proyek        = Proyek::findOrFail($income->proyek_id);
        $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
        $totalIncome   = Income::where('proyek_id', $proyek->id)->where('status', 'approved')->sum('jumlah');
        $totalExpense  = Expense::where('proyek_id', $proyek->id)->sum('amount');
        $available     = $currentBudget - $totalExpense - $totalIncome;

        if ($income->jumlah > $available) {
            abort(response()->json([
                'success' => false,
                'message' => 'Anggaran proyek tidak mencukupi. Sisa anggaran: '.number_format($available),
            ], 422));
        }
    }

    /** Check role helper */
    private function authorizeAdmin(): void
    {
        $role = Auth::user()->role ?? '-';
        if (!in_array($role, ['admin', 'superadmin'])) {
            abort(response()->json(['success'=>false,'message'=>'Akses ditolak.'], 403));
        }
    }

    /* =============================================================== */
    /*  INDEX / LIST                                                   */
    /* =============================================================== */

    public function index(Request $request)
    {
        $q = Income::with(['kategori','paymentMethod','proyek','termin.invoice','createdBy','updatedBy']);
        if ($request->filled('proyek_id'))  $q->where('proyek_id', $request->proyek_id);
        if ($request->filled(['start_date','end_date'])) $q->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        if ($request->filled('status'))     $q->where('status', $request->status);
        if ($request->filled('type'))       $q->where('type', $request->type);

        return response()->json([
            'success' => true,
            'message' => 'List data pemasukan',
            'data'    => IncomeResource::collection($q->latest()->get()),
        ]);
    }

    /* =============================================================== */
    /*  STORE                                                          */
    /* =============================================================== */

     public function store(Request $request)
{
    $this->authorizeAdmin();

    $validated = $request->validate([
        'kategori_id'       => 'required|exists:kategoris,id',
        'payment_method_id' => 'required|exists:payment_methods,id',
        'proyek_id'         => 'nullable|exists:proyeks,id',
        'termin_id'         => 'nullable|exists:termins,id',
        'invoice_id'        => 'nullable|exists:invoices,id',
        'type'              => 'nullable|in:dp,pelunasan',
        'jumlah'            => 'required_without:termin_id|numeric|min:0.01',
        'tanggal'           => 'required|date',
        'deskripsi'         => 'nullable|string',
        'bukti_pembayaran'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        // Tambahkan ini di bagian $validated
'target_progress' => 'nullable|numeric|min:0|max:100',

    ]);

    DB::beginTransaction();
    try {
        $user = Auth::user();
        $income = new Income();
        $income->fill([
            'kode_transaksi'    => $this->generateKodeTransaksi(),
            'kategori_id'       => $validated['kategori_id'],
            'payment_method_id' => $validated['payment_method_id'],
            'proyek_id'         => $validated['proyek_id'] ?? null,
            'deskripsi'         => $validated['deskripsi'] ?? null,
            'tanggal'           => $validated['tanggal'],
            'status'            => 'pending',
            'created_by'        => $user->id,
            'updated_by'        => $user->id,
        ]);

        $termin = null;

        // ---------------- TERMIN EXISTING ----------------
        if (!empty($validated['termin_id'])) {
            $termin = Termin::findOrFail($validated['termin_id']);

            // Validasi status termin
            if (!in_array(strtolower($termin->status_termin), ['dp dibayar', 'lunas'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status termin harus DP Dibayar atau Lunas.',
                ], 422);
            }

            // Validasi proyek dan invoice sinkron
            if ($income->proyek_id && $termin->proyek_id != $income->proyek_id) {
                return response()->json(['message' => 'Termin tidak sesuai proyek.'], 422);
            }
            if ($validated['invoice_id'] && $termin->invoice_id != $validated['invoice_id']) {
                return response()->json(['message' => 'Invoice tidak sesuai dengan termin.'], 422);
            }

        } elseif ($income->proyek_id) {
            // ---------------- TERMIN AUTO-CREATE ----------------
            $nextKe = Termin::where('proyek_id', $income->proyek_id)->max('termin_ke') + 1;
            $jenis  = strtoupper($validated['type'] ?? 'TERMIN BERTAHAP');

            $terminData = [
                'proyek_id'        => $income->proyek_id,
                'kode_termin'      => $this->generateKodeTermin(),
                'nama_termin'      => 'Termin Otomatis ' . $nextKe,
                'target_progress' => $validated['target_progress'] ?? 0,
                'jenis_termin'     => $jenis,
                'termin_ke'        => $jenis === 'TERMIN BERTAHAP' ? $nextKe : null,
                'nilai_termin'     => $validated['jumlah'],
                'persentase_dp'    => $jenis === 'DP' ? 100 : null,
                'nilai_dp'         => $jenis === 'DP' ? $validated['jumlah'] : null,
                'nilai_pelunasan'  => $jenis === 'PELUNASAN' ? $validated['jumlah'] : null,
                'status_termin'    => 'Belum Dibayar',
                'status_approval'  => 'Pending',
                'created_by'       => $user->id,
                'updated_by'       => $user->id,
            ];
            $termin = Termin::create($terminData);
        }

        // ---------------- APPLY TERMIN VALUES ----------------
        if ($termin) {
            $jenis = strtolower($termin->jenis_termin);
            $income->termin_id   = $termin->id;
            $income->invoice_id  = $validated['invoice_id'] ?? $termin->invoice_id;
            $income->type        = in_array($jenis, ['dp', 'pelunasan']) ? $jenis : null;
            $income->jumlah      = $jenis === 'dp'
                ? ($termin->nilai_dp ?? 0)
                : ($jenis === 'pelunasan'
                    ? ($termin->nilai_pelunasan ?? 0)
                    : ($termin->nilai_termin ?? 0));
        } else {
            $income->jumlah      = $validated['jumlah'];
            $income->invoice_id  = $validated['invoice_id'] ?? null;
            $income->type        = $validated['type'] ?? null;
        }
// Wariskan bukti pembayaran dari termin jika income tidak upload file dan termin punya file
if (!$request->hasFile('bukti_pembayaran') && $termin && $termin->bukti_pembayaran) {
    $income->bukti_pembayaran = $termin->bukti_pembayaran;
}

        // ---------------- FILE UPLOAD ----------------
       if ($request->hasFile('bukti_pembayaran')) {
    $file = $request->file('bukti_pembayaran');

    // Simpan ke folder khusus incomes
    $path = $file->storeAs(
        'bukti_pembayaran/incomes',
        time() . '_' . $file->getClientOriginalName(),
        'public'
    );

    $income->bukti_pembayaran = $path;

    if ($termin && !$termin->bukti_pembayaran) {
        // Termin pakai path yang sama (jika ingin sama)
        $termin->bukti_pembayaran = $path;
        $termin->save();
    }
}


        // Final validation
        $this->assertBudgetIsEnough($income);
        $income->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pemasukan berhasil disimpan',
            'data'    => new IncomeResource($income->load(['kategori', 'paymentMethod', 'proyek', 'termin', 'invoice', 'createdBy', 'updatedBy'])),
        ], 201);
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Income store error: '.$e->getMessage());
        return response()->json(['success' => false, 'message' => 'Gagal menyimpan', 'error' => $e->getMessage()], 500);
    }
}

    /* =============================================================== */
    /*  UPDATE                                                          */
    /* =============================================================== */

 public function update(Request $request, int $id)
{
    $this->authorizeAdmin();

    $validated = $request->validate([
        'kategori_id'       => 'required|exists:kategoris,id',
        'payment_method_id' => 'required|exists:payment_methods,id',
        'proyek_id'         => 'nullable|exists:proyeks,id',
        'termin_id'         => 'nullable|exists:termins,id',
        'invoice_id'        => 'nullable|exists:invoices,id',
        'target_progress' => 'nullable|numeric|min:0|max:100',
        'type'              => 'nullable|in:dp,pelunasan',
        'jumlah'            => 'required_without:termin_id|numeric|min:0.01',
        'tanggal'           => 'required|date',
        'deskripsi'         => 'nullable|string',
        'bukti_pembayaran'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
    ]);

    DB::beginTransaction();
    try {
        $user = Auth::user();
        $income = Income::findOrFail($id);
        $income->fill([
            'kategori_id'       => $validated['kategori_id'],
            'payment_method_id' => $validated['payment_method_id'],
            'proyek_id'         => $validated['proyek_id'] ?? null,
            'deskripsi'         => $validated['deskripsi'] ?? null,
            'tanggal'           => $validated['tanggal'],
            'updated_by'        => $user->id,
        ]);

        $termin = null;

        if (!empty($validated['termin_id'])) {
            $termin = Termin::findOrFail($validated['termin_id']);
if (!is_null($validated['target_progress'])) {
    $termin->target_progress = $validated['target_progress'];
    $termin->save();
}

            if (!in_array(strtolower($termin->status_termin), ['dp dibayar', 'lunas'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status termin belum dibayar/lunas.',
                ], 422);
            }

            if ($income->proyek_id && $termin->proyek_id != $income->proyek_id) {
                return response()->json(['message' => 'Termin tidak sesuai proyek.'], 422);
            }
            if ($validated['invoice_id'] && $termin->invoice_id != $validated['invoice_id']) {
                return response()->json(['message' => 'Invoice tidak sesuai dengan termin.'], 422);
            }
        }
if (!$request->hasFile('bukti_pembayaran') && $termin && !$termin->bukti_pembayaran && $income->bukti_pembayaran) {
    $termin->bukti_pembayaran = $income->bukti_pembayaran;
    $termin->save();
}

        if ($termin) {
            $jenis = strtolower($termin->jenis_termin);
            $income->termin_id   = $termin->id;
            $income->invoice_id  = $validated['invoice_id'] ?? $termin->invoice_id;
            $income->type        = in_array($jenis, ['dp', 'pelunasan']) ? $jenis : null;
            $income->jumlah      = $jenis === 'dp'
                ? ($termin->nilai_dp ?? 0)
                : ($jenis === 'pelunasan'
                    ? ($termin->nilai_pelunasan ?? 0)
                    : ($termin->nilai_termin ?? 0));
        } else {
            $income->termin_id   = null;
            $income->invoice_id  = $validated['invoice_id'] ?? null;
            $income->type        = $validated['type'] ?? null;
            $income->jumlah      = $validated['jumlah'];
        }

      if ($request->hasFile('bukti_pembayaran')) {
    if ($income->bukti_pembayaran) {
        Storage::disk('public')->delete($income->bukti_pembayaran);
    }

    $file = $request->file('bukti_pembayaran');
    $path = $file->storeAs(
        'bukti_pembayaran/incomes',
        time() . '_' . $file->getClientOriginalName(),
        'public'
    );

    $income->bukti_pembayaran = $path;

    if ($termin && !$termin->bukti_pembayaran) {
        $termin->bukti_pembayaran = $path;
        $termin->save();
    }
}


        $this->assertBudgetIsEnough($income);
        $income->save();

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Pemasukan berhasil diperbarui',
            'data'    => new IncomeResource($income->load(['kategori', 'paymentMethod', 'proyek', 'termin', 'invoice', 'createdBy', 'updatedBy'])),
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success'=>false,'message'=>'Gagal memperbarui','error'=>$e->getMessage()], 500);
    }
}

    /* =============================================================== */
    /*  APPROVE                                                         */
    /* =============================================================== */

       /**
     * Approve income and synchronise related Termin status/fields
     */
public function approve(int $id)
{
    $this->authorizeAdmin();

    DB::beginTransaction();
    try {
        $income = Income::findOrFail($id);

        if ($income->status === 'approved') {
            return response()->json([
                'success'=>false,
                'message'=>'Pemasukan sudah disetujui sebelumnya.'
            ], 400);
        }

        $income->status     = 'approved';
        $income->updated_by = Auth::id();
        $income->save();
  if ($income->termin) {
    $termin = $income->termin;
    if (strtolower($termin->status_approval) !== 'approved') {
        $termin->status_approval = 'Approved';
        $termin->save();
    }
        DB::commit();
}

        return response()->json([
            'success' => true,
            'message' => 'Pemasukan berhasil disetujui.',
            'data'    => new IncomeResource ($income->load(['kategori','paymentMethod','proyek','termin','invoice'])),
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Approve income error: '.$e->getMessage());
        return response()->json([
            'success'=>false,
            'message'=>'Terjadi kesalahan saat approve.',
            'error'  => $e->getMessage(),
        ], 500);
    }
}

    /* =============================================================== */
    /*  DESTROY                                                         */
    /* =============================================================== */

public function destroy(int $id)
{
    $this->authorizeAdmin(); // Validasi admin

    DB::beginTransaction();

    try {
        $income = Income::with('termin')->findOrFail($id);

        $termin = $income->termin;

        // Hapus file bukti pembayaran income jika ada
        if ($income->bukti_pembayaran) {
            Storage::disk('public')->delete($income->bukti_pembayaran);
        }

        // Simpan dulu jumlah income sebelum hapus
        $incomeCountBeforeDelete = $termin ? $termin->incomes()->count() : 0;

        // Hapus income
        $income->delete();

        // Jika termin hanya punya 1 income DAN belum approved DAN belum dp/lunas, hapus termin
        if (
            $termin &&
            $incomeCountBeforeDelete === 1 &&
            strtolower($termin->status_approval) !== 'approved' &&
            !in_array(strtolower($termin->status_termin), ['dp dibayar', 'lunas'])
        ) {
            // Hapus file bukti pembayaran termin jika sama
            if ($termin->bukti_pembayaran === $income->bukti_pembayaran) {
                Storage::disk('public')->delete($termin->bukti_pembayaran);
                $termin->bukti_pembayaran = null;
            }

            $termin->delete();
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pemasukan berhasil dihapus. Termin juga dihapus jika masih kosong dan belum digunakan.',
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Gagal menghapus pemasukan: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat menghapus pemasukan.',
            'error' => $e->getMessage()
        ], 500);
    }
}



/* =============================================================== */
/*  PATCH STATUS                                                   */
/* =============================================================== */
public function patchStatus(Request $request, int $id)
{
    $this->authorizeAdmin();

    $allowed = ['pending', 'approved', 'rejected'];

    $request->validate([
        'status' => ['required', Rule::in($allowed)],
    ]);

    DB::beginTransaction();
    try {
        $income = Income::with('termin')->findOrFail($id);
        $income->status     = $request->status;
        $income->updated_by = Auth::id();
        $income->save();

        // Sinkronisasi ke termin (jika income memiliki termin)
        if ($request->status === 'approved' && $income->termin) {
            $termin = $income->termin;
            if (strtolower($termin->status_approval) !== 'approved') {
                $termin->status_approval = 'Approved';
                $termin->save();
            }
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Status pemasukan diubah menjadi ' . $request->status,
            'data'    => new IncomeResource($income->load(['kategori', 'paymentMethod', 'proyek', 'termin', 'invoice'])),
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('patchStatus error: '.$e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengubah status pemasukan',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function revokeApproval(int $id)
{
    $this->authorizeAdmin();

    DB::beginTransaction();
    try {
        $income = Income::with('termin')->findOrFail($id);

        if ($income->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Pemasukan belum disetujui.'
            ], 400);
        }

        // Ubah status income
        $income->status = 'pending';
        $income->updated_by = Auth::id();
        $income->save();

        if ($income->termin) {
            $termin = $income->termin;

            $remainingApproved = $termin->incomes()->where('status', 'approved')->exists();

            if (!$remainingApproved) {
                $termin->status_approval = 'pending';

                // Tambahan ini penting
                if (in_array(strtolower($termin->status_termin), ['dp dibayar', 'lunas'])) {
                    $termin->status_termin = 'Belum Dibayar';
                }

                $termin->save();
            }
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Approval pemasukan berhasil di-revoke.',
            'data'    => new IncomeResource($income->load(['termin']))
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Revoke approval error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Gagal melakukan revoke approval',
            'error'   => $e->getMessage(),
        ], 500);
    }
}



}
