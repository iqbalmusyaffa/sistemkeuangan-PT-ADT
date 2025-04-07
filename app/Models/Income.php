<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Company;
use App\Models\Kategori;

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

    // Relationships
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

   /**
     * Automatically generate the transaction code before creating a new record.
     */
   protected static function boot()
   {
       parent::boot();

       static::creating(function ($income) {
           if (empty($income->kode_transaksi)) {  // Ensure kode_transaksi is not already set
               self::generateKodeTransaksi($income);
           }
       });
   }

   /**
     * Generate a unique transaction code based on the current date.
     *
     * @param Income $income
     */
   protected static function generateKodeTransaksi(&$income)
   {
       if ($date = \Carbon\Carbon::parse($income->transaction_date)) {  // Parse date safely using Carbon
           try {
               // Extract year and month from the date
               $year = substr($date->format('Y'), -2);
               $month = str_pad($date->format('m'), 2, "0", STR_PAD_LEFT);

               // Retrieve last transaction code for this month/year combination
               $lastTransaction = self::where('kode_transaksi', 'like', "{$year}.{$month}.%")
                   ->orderBy('kode_transaksi', 'desc')
                   ->first();

               // Determine sequence number; increment last sequence or start at 1 if none exists
               $sequence = ($lastTransaction) ? intval(explode('.', $lastTransaction->kode_transaksi)[2]) + 1 : 1;

               // Format new kode_transaksi as YY.MM.SSS (e.g., "23.04.001")
               $income->kode_transaksi = sprintf("%s.%s.%03d", $year, $month, $sequence);
           } catch (\Exception$e) {
                \Log::error("Error generating kode transaksi: " . $e->getMessage());
            }
       }
   }
   protected $casts = [
    'status' => 'boolean',
];
}
