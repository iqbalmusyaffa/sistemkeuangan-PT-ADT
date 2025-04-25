<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Trackable;
class Kategori extends Model
{
    use HasFactory;
    use Trackable;

    protected $table = 'kategoris';
    protected $fillable = [
        'nama_kategori',
        'jenis',
        'deskripsi'
    ];
}
