<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Proyek;
use App\Models\Kategori;
use App\Models\ServiceCategory;
use App\Models\Purchasematerial;
use App\Models\Termin;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Traits\Trackable;

class Expense extends Model
{
    use HasFactory;
    use Trackable;

    protected $table = 'expenses';

    protected $fillable = [
        'kode_transaksi',
        'user_id',
        'proyek_id',
        'category_id',
        'service_category_id',
        'amount',
        'description',
        'transaction_date',
        'status',
        'payment_method_id', // Note: This field is in fillable but not used in createFromPurchase
        'source_type',
        'source_id',
        'prepared_fund',
        'bukti',
        'invoice_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'prepared_fund' => 'decimal:2',
        'transaction_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $with = ['proyek', 'category', 'serviceCategory'];

    // Tambahkan constant status dan source_type
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_LUNAS = 'Lunas';

    const SOURCE_TERMIN = 'termin';
    const SOURCE_PURCHASE = 'purchase';
    const SOURCE_INVOICE = 'invoice';

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault(function ($user) {
            $user->name = 'Unknown User';
        });
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id')->withDefault(function ($proyek) {
            $proyek->nama_proyek = 'Unknown Project';
            $proyek->nama_customer = 'Unknown Customer';
        });
    }

    public function category()
    {
        return $this->belongsTo(Kategori::class, 'category_id')->withDefault(function ($category) {
            $category->nama_kategori = 'Unknown Category';
        });
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id')->withDefault(function ($category) {
            $category->nama_kategori = 'Unknown Service Category';
        });
    }

    public function source()
    {
        if (empty($this->source_type) || empty($this->source_id)) {
            return null;
        }

        try {
            switch ($this->source_type) {
                case 'termin':
                    return $this->belongsTo(Termin::class, 'source_id');
                case 'purchase':
                    return $this->belongsTo(Purchasematerial::class, 'source_id');
                default:
                    return null;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in Expense source relationship: ' . $e->getMessage());
            return null;
        }
    }

    // Relationship with the Invoice model
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getActiveCategory()
    {
        return $this->service_category_id ? $this->serviceCategory : $this->category;
    }

    public function getCategoryNameAttribute()
    {
        $category = $this->getActiveCategory();
        return $category ? $category->nama_kategori : null;
    }

    public function scopeByProyek($query, $proyekId)
    {
        return $query->where('proyek_id', $proyekId);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->kode_transaksi)) {
                self::generateKodeTransaksi($expense);
            }
        });
    }

    protected static function generateKodeTransaksi(&$expense)
    {
        try {
            $date = Carbon::parse($expense->transaction_date);
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

            $expense->kode_transaksi = sprintf("%s.%s.%03d", $year, $month, $sequence);
        } catch (\Exception $e) {
            Log::error("Error generating kode transaksi: " . $e->getMessage());
        }
    }

    public static function createFromPurchase($purchase)
    {
        if (!$purchase->proyek_id) {
            throw new \Exception("Pembelian harus memiliki proyek.");
        }

        $isService = $purchase->is_service;

        // Ensure expense does not already exist for this purchase
        // This is a safety check; ideally, the caller should prevent duplicate calls
        $existingExpense = self::where('source_type', self::SOURCE_PURCHASE)
            ->where('source_id', $purchase->id)
            ->first();

        if ($existingExpense) {
            // If an expense already exists, return it instead of creating a duplicate
            Log::info("Expense already exists for PurchaseMaterial ID {$purchase->id}. Returning existing expense.");
            return $existingExpense;
        }

        $expense = self::create([
            'user_id' => auth()->id() ?? $purchase->user_id, // Fallback if auth()->id() is null
            'proyek_id' => $purchase->proyek_id,
            'category_id' => $isService ? null : $purchase->category_id,
            'service_category_id' => $isService ? $purchase->service_category_id : null,
            'amount' => $purchase->total_harga,
            'description' => "Pembelian " . ($purchase->item ?? '') . " untuk proyek " . optional($purchase->proyek)->nama_proyek,
            'transaction_date' => now(), // Or use a relevant date from purchase, e.g., $purchase->created_at->toDateString()
            'status' => self::STATUS_LUNAS, // Assuming immediate expense for purchases
            'source_type' => self::SOURCE_PURCHASE,
            'source_id' => $purchase->id,
            'prepared_fund' => $purchase->total_harga,
            'invoice_id' => $purchase->invoice_id
        ]);

        return $expense; // IMPORTANT: Return the created expense instance
    }

    public static function createFromTermin($termin)
    {
        // Add a check to prevent duplicate expenses for the same termin
        $existingExpense = self::where('source_type', self::SOURCE_TERMIN)
            ->where('source_id', $termin->id)
            ->first();

        if ($existingExpense) {
            Log::info("Expense already exists for Termin ID {$termin->id}. Returning existing expense.");
            return $existingExpense;
        }

        $expense = self::create([
            'user_id' => auth()->id(),
            'proyek_id' => $termin->proyek_id,
            'category_id' => $termin->category_id, // Ensure termin has a category_id if needed
            'service_category_id' => null,
            'amount' => $termin->jumlah_pembayaran,
            'description' => "Pembayaran termin {$termin->nama_termin} untuk proyek " . optional($termin->proyek)->nama_proyek,
            'transaction_date' => $termin->tanggal_pembayaran,
            'status' => $termin->status_pembayaran,
            'source_type' => self::SOURCE_TERMIN,
            'source_id' => $termin->id,
            'prepared_fund' => $termin->jumlah_pembayaran,
            'invoice_id' => $termin->invoice_id
        ]);

        return $expense; // IMPORTANT: Return the created expense instance
    }

    public function getSourceInstanceAttribute()
    {
        if (!$this->source_type || !$this->source_id) return null;
        if ($this->source_type === 'termin') {
            return Termin::find($this->source_id);
        }
        if ($this->source_type === 'purchase') {
            return Purchasematerial::find($this->source_id);
        }
        return null;
    }
    public function sourcePurchase()
    {
        return $this->belongsTo(Purchasematerial::class, 'source_id');
    }

    public function sourceTermin()
    {
        return $this->belongsTo(Termin::class, 'source_id');
    }
}
