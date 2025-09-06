<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Merek;
use App\Models\Unit;
use App\Models\Kategori;
use App\Models\ServiceCategory;
use App\Models\Proyek;
use App\Models\Invoice;
use App\Models\Termin;
use App\Models\Expense;
use App\Models\PaymentMethod; // Added PaymentMethod import
use App\Traits\Trackable;
use Illuminate\Support\Facades\Log;

class PurchaseMaterial extends Model
{
    use HasFactory, Trackable;
    protected $table = 'purchase_materials'; // Ini penting

    protected $fillable = [
        'item',
        'merek_id',
        'type',
        'spesifikasi',
        'unit_id',
        'category_id',
        'service_category_id',
        'is_service',
        'qty',
        'harga',
        'total_harga',
        'deskripsi',
        'proyek_id',
        'invoice_id',
        'expense_id',
    ];

    protected $casts = [
        'is_service' => 'boolean',
        'qty' => 'integer',
        'harga' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = [
        'unit',
        'merek',
        'category',
        'serviceCategory',
        'proyek',
    ];

    // Relationships

    public function merek()
    {
        return $this->belongsTo(Merek::class)->withDefault([
            'name' => 'Unknown Merek',
        ]);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class)->withDefault([
            'unit_name' => 'Unknown Unit',
        ]);
    }

    public function category()
    {
        return $this->belongsTo(Kategori::class, 'category_id')->withDefault([
            'nama_kategori' => 'Unknown Category',
        ]);
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id')->withDefault([
            'nama_kategori' => 'Unknown Service Category',
        ]);
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class)->withDefault([
            'nama_proyek' => 'Unknown Project',
        ]);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class)->withDefault();
    }

    public function termin()
    {
        return $this->belongsTo(Termin::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    // Accessor untuk kategori aktif
    public function getActiveCategory()
    {
        return $this->is_service ? $this->serviceCategory : $this->category;
    }

    // Accessor Laravel untuk category_name
    public function getCategoryNameAttribute()
    {
        return $this->getActiveCategory()?->nama_kategori ?? null;
    }

    // Event model untuk hitung total harga otomatis dan buat expense
   protected static function booted()
{
    static::creating(function ($purchase) {
        $purchase->total_harga = $purchase->qty * $purchase->harga;
    });

    static::updating(function ($purchase) {
        $purchase->total_harga = $purchase->qty * $purchase->harga;
    });

    static::saved(function ($purchase) {
        // 1. Update total_amount invoice jika ada
        if ($purchase->invoice) {
            try {
                $purchase->invoice->total_amount = $purchase->invoice->purchaseMaterials()->sum('total_harga');
                $purchase->invoice->save();
                $purchase->invoice->calculateAllFinancialValues();
            } catch (\Exception $e) {
                Log::error("Gagal update total invoice untuk purchase ID {$purchase->id}: {$e->getMessage()}");
            }
        }

        // 2. Buat expense otomatis hanya jika:
        //    - Belum ada expense
        //    - Sudah ada proyek dan invoice valid
        if (
    !empty($purchase->proyek_id) &&
    !empty($purchase->invoice_id) &&
    !$purchase->expense_id &&
    !Expense::where('source_type', Expense::SOURCE_PURCHASE)
        ->where('source_id', $purchase->id)
        ->exists()
)
 {
            // Validasi: invoice harus memiliki proyek_id juga
            if (!$purchase->invoice || empty($purchase->invoice->proyek_id)) {
                Log::warning("Lewati createFromPurchase() karena invoice tidak valid di purchase ID {$purchase->id}");
                return;
            }

            try {
                $expense = Expense::createFromPurchase($purchase);
                $purchase->expense_id = $expense->id;
                $purchase->saveQuietly();
                Log::info("✅ Expense otomatis dibuat untuk PurchaseMaterial ID {$purchase->id}. Expense ID: {$expense->id}");
            } catch (\Exception $e) {
                Log::error("❌ Gagal createFromPurchase() untuk PurchaseMaterial ID {$purchase->id}: {$e->getMessage()}");
            }

        } elseif ($purchase->expense) {
            // 3. Update expense jika sudah ada dan data berubah
            if (
                $purchase->isDirty('total_harga') ||
                $purchase->isDirty('is_service') ||
                $purchase->isDirty('category_id') ||
                $purchase->isDirty('service_category_id') ||
                $purchase->isDirty('proyek_id')
            ) {
                try {
                    $expense = $purchase->expense;

                    $invoiceStatus = $purchase->invoice->status ?? Expense::STATUS_PENDING;
                    $expenseStatus = match ($invoiceStatus) {
                        \App\Models\Invoice::STATUS_PAID => Expense::STATUS_LUNAS,
                        \App\Models\Invoice::STATUS_UNPAID, \App\Models\Invoice::STATUS_PARTIALLY_PAID => Expense::STATUS_PENDING,
                        default => Expense::STATUS_PENDING
                    };

                    $expense->update([
                        'amount' => $purchase->total_harga,
                        'description' => "Pembelian {$purchase->item} untuk proyek " . optional($purchase->proyek)->nama_proyek,
                        'category_id' => $purchase->is_service ? null : $purchase->category_id,
                        'service_category_id' => $purchase->is_service ? $purchase->service_category_id : null,
                        'prepared_fund' => $purchase->total_harga,
                        'status' => $expenseStatus,
                        'proyek_id' => $purchase->proyek_id,
                        'payment_method_id' => $purchase->invoice->payment_method_id ?? null,
                    ]);

                    Log::info("🔁 Expense ID {$expense->id} diperbarui untuk PurchaseMaterial ID {$purchase->id}");
                } catch (\Exception $e) {
                    Log::error("Gagal update expense untuk PurchaseMaterial ID {$purchase->id}: {$e->getMessage()}");
                }
            }
        }
    });

        static::deleted(function ($purchase) {
            // Update total_amount invoice setelah delete
            if ($purchase->invoice) {
                $purchase->invoice->total_amount = $purchase->invoice->purchaseMaterials()->sum('total_harga');
                $purchase->invoice->save();
                // Re-calculate financial values for the invoice
                $purchase->invoice->calculateAllFinancialValues();
            }

            // Optionally delete the associated expense when PurchaseMaterial is deleted
            if ($purchase->expense_id) {
                $expense = Expense::find($purchase->expense_id);
                if ($expense && $expense->source_type === Expense::SOURCE_PURCHASE && $expense->source_id === $purchase->id) {
                    $expense->delete();
                    Log::info("Associated expense ID {$purchase->expense_id} deleted for PurchaseMaterial ID {$purchase->id}.");
                }
            }
        });
    }
}
