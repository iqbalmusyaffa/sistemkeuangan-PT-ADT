<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasbonPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'kasbon_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    public function kasbon(): BelongsTo
    {
        return $this->belongsTo(Kasbon::class);
    }
} 