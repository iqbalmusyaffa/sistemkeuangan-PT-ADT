<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Merek;
use App\Models\Unit;

class Purchasematerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'item', 'merek_id', 'type', 'spesifikasi', 'unit_id', 'qty', 'harga', 'total_harga', 'deskripsi'
    ];

    public function merek()
    {
        return $this->belongsTo(Merek::class); // Relationship with Merek model
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class); // Relationship with Unit model
    }
}
