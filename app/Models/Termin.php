<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Trackable;
class Termin extends Model
{
    use HasFactory;
    use Trackable;


    protected $fillable = [
        'proyek_id',
        'nama_termin',
        'nilai_termin',
        'dp_percentage',
        'nilai_dp',
        'nilai_pelunasan',
        'tanggal_dp',
        'tanggal_pelunasan',
        'status_termin',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_dp' => 'date',
        'tanggal_pelunasan' => 'date',
        'nilai_termin' => 'decimal:2',
        'dp_percentage' => 'decimal:2',
        'nilai_dp' => 'decimal:2',
        'nilai_pelunasan' => 'decimal:2',
    ];

    /**
     * Get the proyek that owns the termin.
     */
    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function purchaseMaterials()
    {
        return $this->hasMany(PurchaseMaterial::class);
    }
}
