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

class Income extends Model
{
    use HasFactory;

    protected $table = 'incomes';

    protected $fillable = [
        'kode_transaksi',
        'tanggal',
        'jumlah',
        'kategori_id',
        'deskripsi',
        'payment_method_id',
        'status',
        'bukti_pembayaran',
        'proyek_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Relasi ke kategori pemasukan.
     */
    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    /**
     * Relasi ke metode pembayaran.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Relasi ke proyek.
     */
    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    /**
     * Relasi ke user yang membuat data.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user yang terakhir mengubah data.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
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
            if (empty($income->kode_transaksi)) {
                self::generateKodeTransaksi($income);
            }
        });
    }

    protected static function generateKodeTransaksi(&$income)
    {
        try {
            $date = Carbon::parse($income->tanggal);
            $year = substr($date->format('Y'), -2);
            $month = str_pad($date->format('m'), 2, "0", STR_PAD_LEFT);

            $lastTransaction = self::where('kode_transaksi', 'like', "{$year}.{$month}.%")
                ->orderBy('kode_transaksi', 'desc')
                ->first();

            $sequence = 1;

            if ($lastTransaction) {
                $parts = explode('.', $lastTransaction->kode_transaksi);
                $lastSeq = count($parts) === 3 ? intval($parts[2]) : 0;
                $sequence = $lastSeq + 1;
            }

            $income->kode_transaksi = sprintf("%s.%s.%03d", $year, $month, $sequence);
        } catch (\Exception $e) {
            Log::error("Error generating kode transaksi: " . $e->getMessage());
        }
    }
}
