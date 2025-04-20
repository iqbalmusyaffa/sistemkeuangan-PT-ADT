<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Termin extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
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
     * Get the project that owns the termin.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
} 