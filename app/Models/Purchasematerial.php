<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Merek;
use App\Models\Unit;
use App\Models\Kategori;
use App\Traits\Trackable;

class Purchasematerial extends Model
{
    use HasFactory;
    use Trackable;

    protected $fillable = [
        'item', 'merek_id', 'type', 'spesifikasi', 'unit_id', 'category_id', 'service_category_id', 'is_service', 'qty', 'harga', 'total_harga', 'deskripsi', 'proyek_id'
    ];

    protected $casts = [
        'is_service' => 'boolean',
        'qty' => 'integer',
        'harga' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    /**
     * Relationship with Merek model.
     */
    public function merek()
    {
        return $this->belongsTo(Merek::class, 'merek_id'); // Foreign key 'merek_id' points to Merek model
    }

    /**
     * Relationship with Unit model.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id'); // Foreign key 'unit_id' points to Unit model
    }

    /**
     * Relationship with Kategori model.
     */
    public function category()
    {
        return $this->belongsTo(Kategori::class, 'category_id'); // Foreign key 'category_id' points to Kategori model
    }

    /**
     * Relationship with Proyek model.
     */
    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id'); // Foreign key 'proyek_id' points to Proyek model
    }

    public function termin()
    {
        return $this->belongsTo(Termin::class);
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id'); // Foreign key 'service_category_id' points to ServiceCategory model
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
}
