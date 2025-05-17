<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Trackable;
use App\Traits\BudgetMonitor;

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
        'tanggal_mulai',
        'tanggal_selesai',
        'status_project',
        'deskripsi'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'anggaran_kontrak' => 'decimal:2'
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

    // ===========================
    // Accessors
    // ===========================

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

    public function getBudgetPercentageAttribute()
    {
        $totalExpenses = $this->expenses()->sum('amount');
        return $this->anggaran_kontrak > 0
            ? round(($totalExpenses / $this->anggaran_kontrak) * 100, 2)
            : 0;
    }

    // ===========================
    // Events
    // ===========================

    protected static function booted()
    {
        static::saved(function ($proyek) {
            $proyek->checkBudgetExceeded();
        });
    }
}
