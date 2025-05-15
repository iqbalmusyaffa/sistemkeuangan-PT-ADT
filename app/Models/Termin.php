<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Trackable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Termin extends Model
{
    use HasFactory;
    use Trackable;
    use SoftDeletes;

    protected $fillable = [
        'proyek_id',
        'invoice_id',
        'nama_termin',
        'nilai_termin',
        'dp_percentage',
        'nilai_dp',
        'nilai_pelunasan',
        'total_dp_paid',
        'total_pelunasan_paid',
        'tanggal_dp',
        'tanggal_pelunasan',
        'status_termin',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_dp' => 'date',
        'tanggal_pelunasan' => 'date',
        'nilai_termin' => 'decimal:2',
        'dp_percentage' => 'decimal:2',
        'nilai_dp' => 'decimal:2',
        'nilai_pelunasan' => 'decimal:2',
        'total_dp_paid' => 'decimal:2',
        'total_pelunasan_paid' => 'decimal:2',
    ];

    protected $appends = [
        'total_paid',
        'remaining_dp',
        'remaining_pelunasan',
        'remaining_total'
    ];

    /**
     * Get the proyek that owns the termin.
     */
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class)->withTrashed();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function purchaseMaterials()
    {
        return $this->hasMany(PurchaseMaterial::class);
    }

    /**
     * Get the incomes associated with this termin.
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Get total pembayaran DP untuk termin ini
     */
    public function getTotalDpPaidAttribute()
    {
        return $this->incomes()
            ->where('type', 'dp')
            ->where('status', 'Diterima')
            ->sum('jumlah');
    }

    /**
     * Get total pembayaran pelunasan untuk termin ini
     */
    public function getTotalPelunasanPaidAttribute()
    {
        return $this->incomes()
            ->where('type', 'pelunasan')
            ->where('status', 'Diterima')
            ->sum('jumlah');
    }

    /**
     * Get total pembayaran untuk termin ini
     */
    public function getTotalPaidAttribute()
    {
        return $this->incomes()
            ->where('status', 'Diterima')
            ->sum('jumlah');
    }

    /**
     * Get remaining DP amount
     */
    public function getRemainingDpAttribute()
    {
        return $this->nilai_dp - $this->total_dp_paid;
    }

    /**
     * Get remaining pelunasan amount
     */
    public function getRemainingPelunasanAttribute()
    {
        return $this->nilai_pelunasan - $this->total_pelunasan_paid;
    }

    /**
     * Get remaining total amount
     */
    public function getRemainingTotalAttribute()
    {
        return $this->nilai_termin - $this->total_paid;
    }

    /**
     * Update termin status based on payments
     */
    public function updateStatusFromPayments()
    {
        $totalDpPaid = $this->total_dp_paid;
        $totalPelunasanPaid = $this->total_pelunasan_paid;

        if ($totalDpPaid >= $this->nilai_dp && $totalPelunasanPaid >= $this->nilai_pelunasan) {
            $this->status_termin = 'Lunas';
        } elseif ($totalDpPaid >= $this->nilai_dp) {
            $this->status_termin = 'DP Dibayar';
        } else {
            $this->status_termin = 'Belum Dibayar';
        }

        $this->save();
    }

    /**
     * Get validation rules for the termin
     */
    public static function getValidationRules($id = null)
    {
        return [
            'proyek_id' => 'required|exists:proyeks,id',
            'invoice_id' => 'required|exists:invoices,id',
            'nama_termin' => 'required|string|max:255',
            'nilai_termin' => 'required|numeric|min:0',
            'dp_percentage' => 'required|numeric|min:0|max:100',
            'nilai_dp' => 'required|numeric|min:0',
            'nilai_pelunasan' => 'required|numeric|min:0',
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp',
            'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
            'keterangan' => 'nullable|string'
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($termin) {
            // Validate total amount
            if ($termin->nilai_dp + $termin->nilai_pelunasan !== $termin->nilai_termin) {
                throw new \Exception('Total DP dan Pelunasan harus sama dengan Nilai Termin');
            }
        });

        static::updating(function ($termin) {
            // Validate status transition
            $oldTermin = static::find($termin->id);
            if ($oldTermin && $oldTermin->status_termin !== $termin->status_termin) {
                static::validateStatusTransition($oldTermin->status_termin, $termin->status_termin);
            }
        });

        static::deleting(function ($termin) {
            // Check if termin can be deleted
            if ($termin->total_paid > 0) {
                throw new \Exception('Tidak dapat menghapus termin yang sudah memiliki pembayaran');
            }
        });

        static::saving(function ($termin) {
            // Calculate totals from incomes
            $termin->total_dp_paid = $termin->incomes()
                ->where('type', 'dp')
                ->where('status', 'Diterima')
                ->sum('jumlah');
                
            $termin->total_pelunasan_paid = $termin->incomes()
                ->where('type', 'pelunasan')
                ->where('status', 'Diterima')
                ->sum('jumlah');

            // Update status based on payments
            if ($termin->isDirty(['total_dp_paid', 'total_pelunasan_paid'])) {
                $termin->updateStatusFromPayments();
            }
        });

        static::created(function ($termin) {
            // Clear any cached calculations
            $termin->clearCache();
        });

        static::updated(function ($termin) {
            // Clear any cached calculations
            $termin->clearCache();
        });

        static::deleted(function ($termin) {
            // Clear any cached calculations
            $termin->clearCache();
        });
    }

    // Status Transition Validation
    protected static function validateStatusTransition($oldStatus, $newStatus)
    {
        $validTransitions = [
            'Belum Dibayar' => ['DP Dibayar', 'Lunas'],
            'DP Dibayar' => ['Lunas'],
            'Lunas' => []
        ];

        if (!in_array($newStatus, $validTransitions[$oldStatus])) {
            throw new \Exception("Tidak dapat mengubah status dari {$oldStatus} ke {$newStatus}");
        }
    }

    // Clear Cache
    public function clearCache()
    {
        Cache::forget("termin_{$this->id}_total_dp_paid");
        Cache::forget("termin_{$this->id}_total_pelunasan_paid");
        Cache::forget("termin_{$this->id}_total_paid");
    }
}
