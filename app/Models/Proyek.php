<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Trackable;
use App\Traits\BudgetMonitor;
use Illuminate\Support\Facades\Log;

class Proyek extends Model
{
    use HasFactory, Trackable, BudgetMonitor;
    // use SoftDeletes; // Optional: aktifkan jika butuh soft delete

    protected $fillable = [
        'user_id',
        'nama_customer',
        'nama_proyek',
        'nama_perusahaan',
        'alamat',
        'no_telp',
        'email',
        'lokasi',
        'anggaran_kontrak',
        'budget_adjusted', // Tambahkan agar bisa diisi mass assignment
        'tanggal_mulai',
        'tanggal_selesai',
        'status_project',
        'deskripsi'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'anggaran_kontrak' => 'decimal:2',
        'budget_adjusted' => 'decimal:2', // cast decimal untuk budget_adjusted
    ];

    // ===========================
    // Relations
    // ===========================

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function termins()
    {
        return $this->hasMany(Termin::class);
    }

    public function purchaseMaterials()
    {
        return $this->hasMany(PurchaseMaterial::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'proyek_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // ===========================
    // Accessors
    // ===========================

    /**
     * Dapatkan anggaran proyek yang berlaku, bisa anggaran kontrak awal
     * atau yang sudah direvisi (budget_adjusted).
     */
    /**
 * Hitung total seluruh pengeluaran proyek dari expenses, purchaseMaterials, dan kasbon disetujui.
 */
    public function getTotalPengeluaranAttribute()
    {
        $pengeluaranLangsung = $this->expenses()->sum('amount');
        $pembelianMaterial = $this->purchaseMaterials()->sum('total_harga'); // kolom yang umum dipakai
        $kasbonDisetujui = $this->hasMany(Kasbon::class)
            ->where('status', 'Disetujui')
            ->sum('amount');

        return $pengeluaranLangsung + $pembelianMaterial + $kasbonDisetujui;
    }

    /**
     * Hitung sisa anggaran proyek (budget - total pengeluaran)
     */
    public function getSisaAnggaranAttribute()
    {
        return $this->current_budget - $this->total_pengeluaran;
    }

    public function getCurrentBudgetAttribute()
    {
        return $this->budget_adjusted ?? $this->anggaran_kontrak;
    }

    public function getTotalIncomeAttribute()
    {
        return $this->termins()
            ->with('incomes')
            ->get()
            ->sum(fn($termin) => $termin->incomes()->where('status', 'Diterima')->sum('jumlah'));
    }

    public function getTotalIncomeDpAttribute()
    {
        return $this->termins()
            ->with('incomes')
            ->get()
            ->sum(fn($termin) => $termin->incomes()->where('type', 'dp')->where('status', 'Diterima')->sum('jumlah'));
    }

    public function getTotalIncomePelunasanAttribute()
    {
        return $this->termins()
            ->with('incomes')
            ->get()
            ->sum(fn($termin) => $termin->incomes()->where('type', 'pelunasan')->where('status', 'Diterima')->sum('jumlah'));
    }

    /**
     * Hitung persentase pengeluaran terhadap anggaran proyek yang berlaku
     * (budget_adjusted jika ada, jika tidak anggaran kontrak awal).
     */
    public function getBudgetPercentageAttribute()
    {
        $totalExpenses = $this->total_pengeluaran;
        $currentBudget = $this->current_budget;

        return $currentBudget > 0
            ? round(($totalExpenses / $currentBudget) * 100, 2)
            : 0;
    }

    // ===========================
    // Methods untuk update status dan validasi anggaran
    // ===========================

    public function updateStatusFromInvoices()
    {
        try {
            $invoices = $this->invoices()->get();

            if ($invoices->isEmpty()) {
                $this->status_project = 'Berjalan';
            } else {
                $allPaid = $invoices->every(fn($inv) => $inv->status === Invoice::STATUS_PAID);
                $this->status_project = $allPaid ? 'Selesai' : 'Berjalan';
            }

            $this->save();
        } catch (\Exception $e) {
            Log::error('Error in updateStatusFromInvoices: ' . $e->getMessage(), [
                'proyek_id' => $this->id,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw the error, just log it
        }
    }

    protected static function booted()
    {
        static::saved(function ($proyek) {
            $proyek->checkBudgetExceeded();
        });
    }

    /**
     * Contoh fungsi untuk validasi budget_adjusted agar tidak melebihi anggaran kontrak.
     * Bisa dipanggil sebelum simpan data proyek.
     */
    public function validateAdjustedBudget()
    {
        if (!is_null($this->budget_adjusted) && $this->budget_adjusted > $this->anggaran_kontrak) {
            throw new \Exception('Budget adjusted tidak boleh lebih besar dari anggaran kontrak awal.');
        }
    }

    // Add a method to reduce the project budget
    public function reduceBudget(float $amount): bool
    {
        if ($this->budget_adjusted !== null) {
            if ($this->budget_adjusted < $amount) return false;
            $this->budget_adjusted -= $amount;
        } else {
            if ($this->anggaran_kontrak < $amount) return false;
            $this->anggaran_kontrak -= $amount;
        }
        return $this->save();
    }

    /**
     * Scope to filter projects above 100 million.
     */
    public function scopeAbove100Million($query)
    {
        return $query->where('anggaran_kontrak', '>', 100000000);
    }

    /**
     * Scope to filter projects below or equal to 100 million.
     */
    public function scopeBelow100Million($query)
    {
        return $query->where('anggaran_kontrak', '<=', 100000000);
    }
}
