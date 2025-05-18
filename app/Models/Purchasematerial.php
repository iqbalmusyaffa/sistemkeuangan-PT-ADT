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
use App\Traits\Trackable;

class PurchaseMaterial extends Model
{
    use HasFactory, Trackable;

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
        return $this->belongsTo(Expense::class);
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

    // Event model untuk hitung total harga otomatis
    protected static function booted()
    {
        static::creating(function ($purchase) {
            $purchase->total_harga = $purchase->qty * $purchase->harga;
        });

        static::updating(function ($purchase) {
            $purchase->total_harga = $purchase->qty * $purchase->harga;
        });

        static::saved(function ($purchase) {
            // Update total_amount invoice setelah create/update
            if ($purchase->invoice) {
                $purchase->invoice->total_amount = $purchase->invoice->purchaseMaterials()->sum('total_harga');
                $purchase->invoice->save();
            }
            // Trigger expense otomatis dari pembelian jika belum ada
            if (!\App\Models\Expense::where('source_type', 'purchase')->where('source_id', $purchase->id)->exists()) {
                \App\Models\Expense::createFromPurchase($purchase);
            }
        });

        static::deleted(function ($purchase) {
            // Update total_amount invoice setelah delete
            if ($purchase->invoice) {
                $purchase->invoice->total_amount = $purchase->invoice->purchaseMaterials()->sum('total_harga');
                $purchase->invoice->save();
            }
        });
    }
}
