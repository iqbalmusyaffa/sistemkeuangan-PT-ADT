<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Merek;
use App\Models\Unit;
use App\Models\Kategori;

class Purchasematerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'item', 'merek_id', 'type', 'spesifikasi', 'unit_id', 'category_id', 'qty', 'harga', 'total_harga', 'deskripsi', 'project_id'
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
     * Relationship with Project model.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id'); // Foreign key 'project_id' points to Project model
    }
}
