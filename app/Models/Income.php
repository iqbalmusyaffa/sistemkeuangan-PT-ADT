<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Kategori;
use App\Models\PaymentMethod;
use App\Models\Proyek;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Termin;
use App\Traits\Trackable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Income extends Model
{
    use HasFactory, Trackable;

    protected $table = 'incomes';

    protected $fillable = [
        'jumlah',
        'status',
        'type',
        'termin_id',
        'kategori_id',
        'payment_method_id',
        'proyek_id',
        'deskripsi',
        'bukti_pembayaran',
        'created_by',
        'updated_by',
        'invoice_id',
        'tanggal' // <--- tambahkan agar mass assignment tanggal bisa
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relasi ke kategori pemasukan.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /**
     * Relasi ke metode pembayaran.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Relasi ke proyek.
     */
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class);
    }

    /**
     * Relasi ke user yang membuat data.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user yang terakhir mengubah data.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relasi ke termin.
     */
    public function termin(): BelongsTo
    {
        return $this->belongsTo(Termin::class);
    }

    /**
     * Relasi ke invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }

    // =====================
    // == CUSTOM ACCESSORS =
    // =====================
    public function getFormattedDateAttribute()
    {
        return $this->tanggal ? Carbon::parse($this->tanggal)->format('d-m-Y') : null;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->jumlah, 0, ',', '.');
    }

    // =====================
    // == SCOPES ===========
    // =====================
    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('tanggal', $year)
                     ->whereMonth('tanggal', $month);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Diterima');
    }

    // =====================
    // == AUTO-GENERATE ====
    // =====================
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($income) {
            $income->kode_transaksi = 'INV-' . strtoupper(Str::random(8));
        });

        static::saved(function ($income) {
            if ($income->termin_id) {
                $termin = $income->termin;
                if ($termin) {
                    $termin->updateStatusFromPayments();
                }
            }
        });
    }
}
