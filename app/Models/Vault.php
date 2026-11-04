<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vault extends Model
{
    protected $fillable = [
        'name',
        'initial_balance',
        'annual_yield_rate',
        'yield_cap',
    ];

    /**
     * Los totales que agrega `withMovements()` no forman parte del contrato público.
     */
    protected $hidden = ['total_incomes', 'total_expenses'];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'annual_yield_rate' => 'decimal:2',
            'yield_cap' => 'decimal:2',
        ];
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Precarga los totales de movimientos para evitar N+1 al calcular saldos.
     */
    public function scopeWithMovements(Builder $query): void
    {
        $query->withSum('incomes as total_incomes', 'amount')
            ->withSum('expenses as total_expenses', 'amount');
    }

    /**
     * El saldo NO se almacena: initial_balance + ingresos - gastos.
     */
    public function currentBalance(): float
    {
        $incomes = $this->total_incomes ?? $this->incomes()->sum('amount');
        $expenses = $this->total_expenses ?? $this->expenses()->sum('amount');

        return (float) $this->initial_balance + (float) $incomes - (float) $expenses;
    }

    /**
     * Expone el saldo calculado como atributo `balance` para el frontend.
     */
    public function withComputedBalance(): self
    {
        $this->balance = $this->currentBalance();

        return $this;
    }

    /**
     * Saldo sobre el que realmente se calcula el rendimiento (respeta el tope).
     */
    public function effectiveBalance(): float
    {
        $balance = $this->balance ?? $this->currentBalance();

        if ($this->yield_cap !== null && $balance > (float) $this->yield_cap) {
            return (float) $this->yield_cap;
        }

        return (float) $balance;
    }

    public function annualYield(): float
    {
        $rate = (float) $this->annual_yield_rate;

        if ($rate <= 0) {
            return 0.0;
        }

        return $this->effectiveBalance() * ($rate / 100);
    }

    public function monthlyYield(): float
    {
        return $this->annualYield() / 12;
    }

    public function dailyYield(): float
    {
        return $this->annualYield() / 365;
    }
}
