<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Purchasematerial;
use App\Traits\Trackable;
class Merek extends Model
{
    use HasFactory;
    use Trackable;

    protected $fillable = ['name'];
    public function purchasematerials()
    {
        return $this->hasMany(Purchasematerial::class);
    }
}
