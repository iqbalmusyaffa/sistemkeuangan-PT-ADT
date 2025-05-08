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
    protected $appends = ['file_url'];
    protected $casts = [
        'file_path' => 'string',
        'file_name' => 'string',
        'file_type' => 'string'
    ];

    public function kasbon(): BelongsTo
    {
        return $this->belongsTo(Kasbon::class);
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
