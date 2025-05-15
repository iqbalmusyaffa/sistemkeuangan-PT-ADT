<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Trackable;
use App\Traits\BudgetMonitor;

class Proyek extends Model
{
    use HasFactory, Trackable, BudgetMonitor;

    protected $fillable = [
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

    /**
     * Get the termins for the project.
     */
    public function termins()
    {
        return $this->hasMany(Termin::class);
    }

    /**
     * Get the purchase materials for the project.
     */
    public function purchaseMaterials()
    {
        return $this->hasMany(PurchaseMaterial::class);
    }

    public function expenses()
    {
        return $this->hasMany(\App\Models\Expense::class, 'proyek_id');
    }

    /**
     * Get the user associated with the project.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::saved(function ($proyek) {
            // Check if budget is exceeded whenever the project is saved
            $proyek->checkBudgetExceeded();
        });
    }

    public function getTotalIncomeAttribute()
    {
        return $this->termins()
            ->with('incomes')
            ->get()
            ->sum(function ($termin) {
                return $termin->incomes()
                    ->where('status', 'Diterima')
                    ->sum('jumlah');
            });
    }

    public function getTotalIncomeDpAttribute()
    {
        return $this->termins()
            ->with('incomes')
            ->get()
            ->sum(function ($termin) {
                return $termin->incomes()
                    ->where('type', 'dp')
                    ->where('status', 'Diterima')
                    ->sum('jumlah');
            });
    }

    public function getTotalIncomePelunasanAttribute()
    {
        return $this->termins()
            ->with('incomes')
            ->get()
            ->sum(function ($termin) {
                return $termin->incomes()
                    ->where('type', 'pelunasan')
                    ->where('status', 'Diterima')
                    ->sum('jumlah');
            });
    }
}
