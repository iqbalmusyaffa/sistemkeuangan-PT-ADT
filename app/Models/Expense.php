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
use App\Models\PaymentMethod; // Added PaymentMethod import
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
        'payment_method_id',
        'source_type',
        'source_id',
        'prepared_fund',
        'bukti_pembayaran',
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
protected $appends = ['url_bukti'];

    // Status yang diizinkan untuk income/expense/termin
    const STATUS_PENDING = 'pending';
    const STATUS_DP_SEBAGIAN = 'DP Sebagian';
    const STATUS_DP_DIBAYAR = 'DP Dibayar';
    const STATUS_PELUNASAN_SEBAGIAN = 'Pelunasan Sebagian';
    const STATUS_BELUM_DIBAYAR = 'Belum Dibayar';
    const STATUS_LUNAS = 'Lunas';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

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

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class)->withDefault([
            'name' => 'Unknown Payment Method',
        ]);
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
        $existingExpense = self::where('source_type', self::SOURCE_PURCHASE)
            ->where('source_id', $purchase->id)
            ->first();

        if ($existingExpense) {
            Log::info("Expense already exists for PurchaseMaterial ID {$purchase->id}. Returning existing expense.");
            return $existingExpense;
        }

        // Map Invoice status to Expense status
        $expenseStatus = self::STATUS_PENDING; // Default to pending
        if ($purchase->invoice) {
            switch ($purchase->invoice->status) {
                case Invoice::STATUS_PAID:
                    $expenseStatus = self::STATUS_LUNAS;
                    break;
                case Invoice::STATUS_PARTIALLY_PAID:
                    $expenseStatus = self::STATUS_PENDING;
                    break;
                case Invoice::STATUS_UNPAID:
                case Invoice::STATUS_CANCELLED:
                    $expenseStatus = self::STATUS_PENDING;
                    break;
                default:
                    $expenseStatus = self::STATUS_PENDING;
                    break;
            }
        }

        $expense = self::create([
            'user_id' => auth()->id() ?? $purchase->user_id, // Fallback if auth()->id() is null
            'proyek_id' => $purchase->proyek_id,
            'category_id' => $isService ? null : $purchase->category_id,
            'service_category_id' => $isService ? $purchase->service_category_id : null,
            'amount' => $purchase->total_harga,
            'description' => "Pembelian " . ($purchase->item ?? '') . " untuk proyek " . optional($purchase->proyek)->nama_proyek,
            'transaction_date' => now(), // Or use a relevant date from purchase
            'status' => $expenseStatus, // SET STATUS BASED ON MAPPED INVOICE STATUS
            'source_type' => self::SOURCE_PURCHASE,
            'source_id' => $purchase->id,
            'prepared_fund' => $purchase->total_harga,
            'payment_method_id' => $purchase->invoice ? $purchase->invoice->payment_method_id : null, // Get payment method from invoice
            'invoice_id' => $purchase->invoice_id
        ]);

        return $expense; // IMPORTANT: Return the created expense instance
    }

    public static function createFromTermin($termin)
    {
        return \DB::transaction(function () use ($termin) {
            $existingExpense = self::where('source_type', self::SOURCE_TERMIN)
                ->where('source_id', $termin->id)
                ->first();

            $expenseStatus = ($termin->status_termin === 'Lunas' || $termin->status_termin === 'DP Dibayar')
                ? self::STATUS_LUNAS
                : self::STATUS_PENDING;

            $expenseData = [
                'user_id' => auth()->id() ?? $termin->created_by,
                'proyek_id' => $termin->proyek_id,
                'category_id' => null,
                'service_category_id' => null,
                'amount' => $termin->nilai_termin,
                'description' => "Pengeluaran Termin {$termin->nama_termin} untuk proyek " . optional($termin->proyek)->nama_proyek,
                'transaction_date' => $termin->tanggal_pelunasan_dibayar
                    ?? $termin->tanggal_dp_dibayar
                    ?? $termin->tanggal_pelunasan
                    ?? $termin->tanggal_dp
                    ?? now(),
                'status' => $expenseStatus,
                'source_type' => self::SOURCE_TERMIN,
                'source_id' => $termin->id,
                'prepared_fund' => $termin->nilai_termin,
                'payment_method_id' => $termin->invoice ? $termin->invoice->payment_method_id : null,
                'invoice_id' => $termin->invoice_id
            ];

            if ($existingExpense) {
                Log::info("Expense already exists for Termin ID {$termin->id}. Updating existing expense.");
                $existingExpense->update($expenseData);
                $expense = $existingExpense;
            } else {
                $expense = self::create($expenseData);
            }

            // Otomatis update status termin & invoice setelah expense dibuat/diupdate
            if (method_exists($termin, 'syncStatus')) {
                $termin->syncStatus();
            }
            if ($termin->invoice && method_exists($termin->invoice, 'syncStatus')) {
                $termin->invoice->syncStatus();
            }

            return $expense;
        });
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
    public function getUrlBuktiAttribute()
{
    return $this->bukti ? asset('storage/' . $this->bukti) : null;
}
}
