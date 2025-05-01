<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasbonAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'kasbon_id',
        'file_path',
        'file_name',
        'file_type'
    ];

    public function kasbon(): BelongsTo
    {
        return $this->belongsTo(Kasbon::class);
    }
} 