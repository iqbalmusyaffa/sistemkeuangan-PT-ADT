<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Purchasematerial;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_name', 'unit_code'
    ];

    public function purchasematerials()
    {
        return $this->hasMany(Purchasematerial::class); // One unit can have many purchasematerials
    }
}
