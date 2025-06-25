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

    // Status yang diizinkan untuk income/expense
    const STATUS_PENDING = 'pending';
    const STATUS_DP_SEBAGIAN = 'DP Sebagian';
    const STATUS_DP_DIBAYAR = 'DP Dibayar';
    const STATUS_PELUNASAN_SEBAGIAN = 'Pelunasan Sebagian';
    const STATUS_BELUM_DIBAYAR = 'Belum Dibayar';
    const STATUS_LUNAS = 'Lunas';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
// Tambahkan di bagian atas dalam class Income
const APPROVAL_PENDING = 'pending';
const APPROVAL_APPROVED = 'approved';
const APPROVAL_REJECTED = 'rejected';

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

        static::updated(function ($income) {
            // Sync ke termin jika income terkait termin
            if ($income->termin) {
                $income->termin->updateStatusFromPayments();
            }
        });

        static::saved(function ($income) {
            // Update status termin jika ada
            if ($income->termin_id) {
                $termin = $income->termin;
                if ($termin) {
                    $termin->clearCache();
                    $termin->updateStatusFromPayments();
                    $termin->refresh(); // Pastikan status_termin terbaru
                }
            }
            // Update status invoice jika ada
            if ($income->invoice_id) {
                $invoice = $income->invoice;
                if ($invoice) {
                    // Hitung ulang total paid
                    $totalPaid = \App\Models\Income::where('invoice_id', $income->invoice_id)
                        ->where('status', 'Diterima')
                        ->sum('jumlah');
                    $invoice->amount_paid = $totalPaid;
                    $invoice->status = $invoice->determineStatus();
                    $invoice->save();
                    $invoice->refresh(); // Pastikan status terbaru
                    $invoice->updateStatusFromTermins();
                }
            }
        });
    }
public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updatedBy(): BelongsTo
{
    return $this->updater();
}
}
