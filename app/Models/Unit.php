<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Purchasematerial;
use App\Traits\Trackable;

class Unit extends Model
{
    use HasFactory;
    use Trackable;


    protected $fillable = [
        'unit_name', 'unit_code'
    ];

    public function purchasematerials()
    {
        return $this->hasMany(Purchasematerial::class); // One unit can have many purchasematerials
    }
    public function serviceCategories()
    {
        return $this->hasMany(ServiceCategory::class); // One unit can have many service categories
    }
}
