<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kasbon extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyek_id',
        'user_id',
        'nomor_kasbon',
        'amount',
        'description',
        'kasbon_date',
        'due_date',
        'status',
        'payment_method',
        'bank_account',
        'bank_name',
        'account_number',
        'account_holder',
        'rejection_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'kasbon_date' => 'date',
        'due_date' => 'date'
    ];

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KasbonAttachment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(KasbonPayment::class);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->payments()->sum('amount');
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_amount <= 0;
    }
} 