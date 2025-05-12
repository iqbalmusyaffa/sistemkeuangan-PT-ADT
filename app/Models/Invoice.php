<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyek_id',
        'payment_method_id',
        'invoice_number',
        'invoice_date',
        'total_amount',
        'amount_paid',
        'pph_non_final_amount',
        'pph_final_amount',
        'ppn_amount',
        'net_profit',
        'use_ppn',
        'use_pph_non_final',
        'use_pph_final',
        'notes',
        'status'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'pph_non_final_amount' => 'decimal:2',
        'pph_final_amount' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'use_ppn' => 'boolean',
        'use_pph_non_final' => 'boolean',
        'use_pph_final' => 'boolean',
        'invoice_date' => 'date',
        'status' => 'string'
    ];

    // Relationships
    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function purchaseMaterials()
    {
        return $this->hasMany(PurchaseMaterial::class, 'invoice_id');
    }

    public function termins()
    {
        return $this->hasMany(Termin::class, 'invoice_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'invoice_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class);
    }

    // Status Management
    public function determineStatus()
    {
        if ($this->amount_paid == 0) {
            return 'unpaid';
        } elseif ($this->amount_paid < $this->total_amount) {
            return 'partially_paid';
        } elseif ($this->amount_paid >= $this->total_amount) {
            return 'paid';
        } else {
            return 'unpaid';
        }
    }

    public function setAmountPaidAttribute($value)
    {
        $this->attributes['amount_paid'] = $value;
        $this->attributes['status'] = $this->determineStatus();
    }

    public function updateStatusFromTermins()
    {
        $termins = $this->termins;
        if ($termins->count() === 0) {
            $this->status = 'unpaid';
        } elseif ($termins->every(fn($t) => $t->status_termin === 'Lunas')) {
            $this->status = 'paid';
        } elseif ($termins->every(fn($t) => $t->status_termin === 'Belum Dibayar')) {
            $this->status = 'unpaid';
        } elseif ($termins->every(fn($t) => $t->status_termin === 'DP Dibayar')) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'partially_paid';
        }
        $this->save();
    }

    // Query Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('status', 'partially_paid');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Accessors
    public function getTotalTaxAttribute()
    {
        return $this->pph_non_final_amount + $this->pph_final_amount + $this->ppn_amount;
    }

    public function getTotalWithTaxAttribute()
    {
        return $this->total_amount + $this->total_tax;
    }

    public function getTotalBarangAttribute()
    {
        return $this->purchaseMaterials()
            ->where('is_service', false)
            ->sum('total_harga');
    }

    public function getTotalJasaAttribute()
    {
        return $this->purchaseMaterials()
            ->where('is_service', true)
            ->sum('total_harga');
    }
}
