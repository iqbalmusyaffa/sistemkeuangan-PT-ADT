<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_customer',
        'nama_project',
        'lokasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'anggaran_kontrak',
        'status_project',
        'deskripsi',
    ];

    /**
     * Get the termins for the project.
     */
    public function termins()
    {
        return $this->hasMany(Termin::class);
    }
}
