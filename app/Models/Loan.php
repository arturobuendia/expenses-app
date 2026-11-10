<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    /**
     * `direction` indica quién debe: `lent` cuando el usuario prestó,
     * `borrowed` cuando el usuario pidió prestado.
     */
    protected $fillable = [
        'counterparty_name',
        'direction',
        'amount',
        'description',
        'date',
        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'is_paid' => 'boolean',
        ];
    }

    /**
     * Abonos (pagos parciales) registrados sobre este préstamo.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    /**
     * Total abonado. Los préstamos liquidados de forma manual (is_paid) se
     * consideran abonados en su totalidad.
     */
    public function paidAmount(): float
    {
        if ($this->is_paid) {
            return (float) $this->amount;
        }

        return (float) $this->payments->sum('amount');
    }

    /**
     * Saldo pendiente: monto original menos lo abonado, nunca negativo.
     */
    public function remainingAmount(): float
    {
        if ($this->is_paid) {
            return 0.0;
        }

        return (float) max(0.0, (float) $this->amount - $this->paidAmount());
    }

    /**
     * Se liquida cuando el saldo pendiente llega a cero, ya sea por abonos
     * o por haberlo marcado como pagado.
     */
    public function isSettled(): bool
    {
        return $this->is_paid || $this->remainingAmount() <= 0;
    }

    /**
     * Expone los valores calculados para el frontend.
     */
    public function withComputedPaymentTotals(): self
    {
        $this->paid_amount = $this->paidAmount();
        $this->remaining_amount = $this->remainingAmount();
        $this->is_settled = $this->isSettled();

        return $this;
    }
}
