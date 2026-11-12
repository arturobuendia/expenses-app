<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'name',
        'description',
        'starts_at',
        'ends_at',
        'budget',
        'archived_at',
    ];

    /**
     * El total gastado se agrega con `withSpent()`, no forma parte del
     * contrato público.
     */
    protected $hidden = ['total_spent'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'budget' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Precarga el total gastado y el conteo de movimientos para evitar N+1.
     */
    public function scopeWithSpent(Builder $query): void
    {
        $query->withSum(
            ['expenses as total_spent' => fn (Builder $q) => $q->where('is_transfer', false)],
            'amount'
        )->withCount('expenses');
    }

    /**
     * Total gastado (sin transferencias, que solo mueven saldo).
     */
    public function totalSpent(): float
    {
        $spent = $this->total_spent
            ?? $this->expenses()->where('is_transfer', false)->sum('amount');

        return (float) $spent;
    }

    /**
     * Presupuesto disponible. Null si el evento no define presupuesto.
     */
    public function remainingBudget(): ?float
    {
        if ($this->budget === null) {
            return null;
        }

        return (float) $this->budget - $this->totalSpent();
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /**
     * Expone los valores calculados para el frontend.
     */
    public function withComputedTotals(): self
    {
        $this->spent = $this->totalSpent();
        $this->remaining_budget = $this->remainingBudget();
        $this->is_archived = $this->isArchived();

        return $this;
    }
}
