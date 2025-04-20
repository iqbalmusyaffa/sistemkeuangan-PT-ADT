<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Purchasematerial;

class Merek extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'deskripsi'];
    public function purchasematerials()
    {
        return $this->hasMany(Purchasematerial::class);
    }
}
