<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPayment extends Model
{
    /**
     * Un abono (pago parcial) sobre un préstamo. Se acumulan para calcular
     * cuánto queda pendiente sin tener que cerrar el préstamo y crear otro.
     */
    protected $fillable = [
        'loan_id',
        'amount',
        'date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
