<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Trackable;
class Proyek extends Model
{
    use HasFactory;
    use Trackable;

    protected $fillable = [
        'nama_customer',
        'nama_proyek',
        'nama_perusahaan',
        'alamat',
        'no_telp',
        'email',
        'lokasi',
        'anggaran_kontrak',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_project',
        'deskripsi'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'anggaran_kontrak' => 'decimal:2'
    ];

    /**
     * Get the termins for the project.
     */
    public function termins()
    {
        return $this->hasMany(Termin::class);
    }

    /**
     * Get the purchase materials for the project.
     */
    public function purchaseMaterials()
    {
        return $this->hasMany(PurchaseMaterial::class);
    }
}
