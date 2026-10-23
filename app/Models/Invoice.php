<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    protected $fillable = [
        'number',
        'order_id',
        'customer_id',
        'invoiced_on',
        'subtotal',
        'tax_total',
        'grand_total',
        'tax_lines',
        'schedule_snapshot',
        'lines_snapshot',
        'status',
        'payment_gateway_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoiced_on' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'tax_lines' => 'array',
            'schedule_snapshot' => 'array',
            'lines_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function outstanding(): float
    {
        return round((float) $this->grand_total - $this->amountPaid(), 2);
    }

    public function refreshPaymentStatus(): void
    {
        if ($this->status === 'void') {
            return;
        }

        $paid = $this->amountPaid();
        $total = (float) $this->grand_total;

        if ($paid <= 0.0001) {
            $this->status = 'issued';
        } elseif ($paid + 0.0001 >= $total) {
            $this->status = 'paid';
        } else {
            $this->status = 'partial';
        }
        $this->save();
    }
}
