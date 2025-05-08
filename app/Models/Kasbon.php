<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\KasbonStatus;
use Illuminate\Support\Str;

class Kasbon extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyek_id',
        'user_name',
        'nomor_kasbon',
        'amount',
        'description',
        'kasbon_date',
        'due_date',
        'approval_date',
        'disbursement_date',
        'settlement_date',
        'status',
        'payment_method',
        'bank_account',
        'bank_name',
        'account_number',
        'account_holder',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'kasbon_date' => 'date',
        'due_date' => 'date',
        'status' => KasbonStatus::class,  // This should be the Enum class

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kasbon) {
            if (empty($kasbon->nomor_kasbon)) {
                $kasbon->nomor_kasbon = 'KB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }
    protected $appends = ['remaining_amount', 'is_fully_paid', 'status_label'];

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KasbonAttachment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(KasbonPayment::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->payments()->sum('amount');
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->remaining_amount <= 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }
}
