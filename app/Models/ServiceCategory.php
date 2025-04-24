<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Trackable;
class ServiceCategory extends Model
{
    use Trackable;
    use HasFactory;
    protected $table = 'service_categories';

    protected $fillable = [
        'nama_kategori',
        'jenis',
        'harga',
        'unit_id',
        'deskripsi'
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
     public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
