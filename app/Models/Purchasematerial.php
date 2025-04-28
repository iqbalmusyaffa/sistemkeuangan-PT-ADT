<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Merek;
use App\Models\Unit;
use App\Models\Kategori;
use App\Traits\Trackable;

class PurchaseMaterial extends Model
{
    use HasFactory;
    use Trackable;

    protected $fillable = [
        'item', 'merek_id', 'type', 'spesifikasi', 'unit_id', 'category_id', 'service_category_id', 'is_service', 'qty', 'harga', 'total_harga', 'deskripsi', 'proyek_id', 'invoice_id'
    ];

    protected $casts = [
        'is_service' => 'boolean',
        'qty' => 'integer',
        'harga' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $with = ['unit', 'merek', 'category', 'serviceCategory'];

    /**
     * Relationship with Merek model.
     */
    public function merek()
    {
        return $this->belongsTo(Merek::class, 'merek_id')->withDefault(function ($merek) {
            $merek->name = 'Unknown Merek';
        });
    }

    /**
     * Relationship with Unit model.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id')->withDefault(function ($unit) {
            $unit->unit_name = 'Unknown Unit';
        });
    }

    /**
     * Relationship with Kategori model.
     */
    public function category()
    {
        return $this->belongsTo(Kategori::class, 'category_id')->withDefault(function ($category) {
            $category->nama_kategori = 'Unknown Category';
        });
    }

    /**
     * Relationship with Proyek model.
     */
    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id')->withDefault(function ($proyek) {
            $proyek->nama_proyek = 'Unknown Project';
        });
    }

    /**
     * Relationship with Invoice model.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id')->withDefault(function ($invoice) {
            $invoice->invoice_number = 'Unknown Invoice';
        });
    }

    /**
     * Relationship with Termin model.
     */
    public function termin()
    {
        return $this->belongsTo(Termin::class);
    }

    /**
     * Relationship with ServiceCategory model.
     */
    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id')->withDefault(function ($category) {
            $category->nama_kategori = 'Unknown Service Category';
        });
    }

    public function getActiveCategory()
    {
        return $this->is_service ? $this->serviceCategory : $this->category;
    }

    public function getCategoryNameAttribute()
    {
        $category = $this->getActiveCategory();
        return $category ? $category->nama_kategori : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            $purchase->total_harga = $purchase->qty * $purchase->harga;
        });

        static::updating(function ($purchase) {
            $purchase->total_harga = $purchase->qty * $purchase->harga;
        });
    }
}
