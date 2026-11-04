<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'name',
        'amount',
        'card_id',
        'category_id',
        'next_billing_date',
        'frequency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_billing_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Costo equivalente mensual, para que una suscripción anual no infle
     * el gasto mensual con su monto completo.
     */
    public function monthlyAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->frequency === 'annual' ? $amount / 12 : $amount;
    }
}
