<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Kategori;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Income extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_transaksi',
        'user_id',
        'company_id',
        'category_id',
        'amount',
        'description',
        'transaction_date',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'transaction_date' => 'date',
    ];

    // =====================
    // == RELATIONSHIPS ===
    // =====================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Kategori::class);
    }

    // =====================
    // == CUSTOM ACCESSORS =
    // =====================
    public function getFormattedDateAttribute()
    {
        return $this->transaction_date ? $this->transaction_date->format('d-m-Y') : null;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }

    // =====================
    // == SCOPES ===========
    // =====================
    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('transaction_date', $year)
                     ->whereMonth('transaction_date', $month);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
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
            $date = Carbon::parse($income->transaction_date);
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
