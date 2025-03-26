<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Kategori;
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_transaksi',
        'user_id',
        'category_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'status'
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            $date = $transaction->transaction_date;
            $year = substr($date->format('Y'), -2);
            $month = $date->format('m');
            $yearMonth = "{$year}.{$month}";

            $lastTransaction = Transaction::where('kode_transaksi', 'like', "{$yearMonth}.%")
                ->orderBy('kode_transaksi', 'desc')
                ->first();

            $sequence = $lastTransaction ? intval(explode('.', $lastTransaction->kode_transaksi)[2]) + 1 : 1;
            $transaction->kode_transaksi = sprintf("%s.%03d", $yearMonth, $sequence);
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Kategori::class);
    }
}
