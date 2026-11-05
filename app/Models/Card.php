<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    protected $fillable = [
        'name',
        'credit_limit',
        'cut_off_day',
        'payment_day',
        'is_active',
    ];

    protected $hidden = ['total_charges', 'total_payments'];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'cut_off_day' => 'integer',
            'payment_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function statements(): HasMany
    {
        return $this->hasMany(CardStatement::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Compras: dinero que se debe.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Expense::class)->where('is_transfer', false);
    }

    /**
     * Pagos: dinero que sale de una bóveda para liquidar la tarjeta.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Expense::class)->where('is_transfer', true);
    }

    public function scopeWithTotals(Builder $query): void
    {
        $query->withSum(['expenses as total_charges' => fn (Builder $q) => $q->where('is_transfer', false)], 'amount')
            ->withSum(['expenses as total_payments' => fn (Builder $q) => $q->where('is_transfer', true)], 'amount');
    }

    /**
     * La deuda se calcula, no se almacena: cargos menos pagos.
     */
    public function currentDebt(): float
    {
        $charges = $this->total_charges ?? $this->charges()->sum('amount');
        $payments = $this->total_payments ?? $this->payments()->sum('amount');

        return (float) $charges - (float) $payments;
    }

    public function availableCredit(): float
    {
        return (float) $this->credit_limit - $this->currentDebt();
    }

    /**
     * Expone los valores calculados para el frontend.
     */
    public function withComputedTotals(): self
    {
        $this->debt = $this->currentDebt();
        $this->available_credit = $this->availableCredit();

        return $this;
    }

    /**
     * Ciclo de facturación al que pertenece una fecha.
     *
     * @return array{period_start: Carbon, period_end: Carbon, cut_date: Carbon, due_date: Carbon}
     */
    public function cycleFor(CarbonInterface|string $date): array
    {
        $date = Carbon::parse($date)->startOfDay();

        $cut = $this->cutDateFor($date->year, $date->month);

        if ($date->greaterThan($cut)) {
            $next = $date->copy()->addMonthNoOverflow();
            $cut = $this->cutDateFor($next->year, $next->month);
        }

        $previous = $cut->copy()->subMonthNoOverflow();

        return [
            'period_start' => $this->cutDateFor($previous->year, $previous->month)->addDay(),
            'period_end' => $cut->copy(),
            'cut_date' => $cut->copy(),
            'due_date' => $this->dueDateFor($cut),
        ];
    }

    /**
     * Crea (o reutiliza) el estado de cuenta del ciclo de esa fecha.
     */
    public function statementFor(CarbonInterface|string $date): CardStatement
    {
        $cycle = $this->cycleFor($date);

        return $this->statements()->firstOrCreate(
            ['cut_date' => $cycle['cut_date']->toDateString()],
            [
                'period_start' => $cycle['period_start']->toDateString(),
                'period_end' => $cycle['period_end']->toDateString(),
                'due_date' => $cycle['due_date']->toDateString(),
            ]
        );
    }

    /**
     * Estados de cuenta con su liquidación FIFO: los pagos cubren primero los
     * cortes más viejos.
     *
     * @return \Illuminate\Support\Collection<int, CardStatement>
     */
    public function settledStatements(): \Illuminate\Support\Collection
    {
        // Las parcialidades de MSI no traen estado de cuenta; si el ciclo ya
        // cortó, se materializa para que aparezca con su fecha límite.
        Installment::query()
            ->where('card_id', $this->id)
            ->whereDate('cut_date', '<=', now()->toDateString())
            ->distinct()
            ->pluck('cut_date')
            ->each(fn ($cut) => $this->statementFor($cut));

        $statements = $this->statements()
            ->withSum('charges as total_charges', 'amount')
            ->orderBy('cut_date')
            ->get();

        // Las parcialidades no viven en el estado de cuenta, se facturan por
        // fecha de corte. Se agrupan una sola vez para no hacer N+1.
        $installmentsByCut = Installment::query()
            ->where('card_id', $this->id)
            ->get()
            ->groupBy(fn (Installment $installment) => $installment->cut_date->toDateString())
            ->map(fn ($group) => (float) $group->sum('amount'));

        $statements->each(function (CardStatement $statement) use ($installmentsByCut) {
            $statement->installment_total = $installmentsByCut[$statement->cut_date->toDateString()] ?? 0.0;
        });

        $pool = (float) $this->payments()->sum('amount');

        return $statements->map(function (CardStatement $statement) use (&$pool) {
            $total = $statement->totalAmount();
            $paid = min($pool, $total);
            $pool -= $paid;

            $statement->paid_amount = round($paid, 2);
            $statement->pending_amount = round($total - $paid, 2);
            $statement->is_settled = $paid >= $total;

            return $statement;
        });
    }

    /**
     * Día de corte del mes. El 31 se recorta al último día disponible.
     */
    public function cutDateFor(int $year, int $month): Carbon
    {
        $firstOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $day = min($this->cut_off_day, $firstOfMonth->daysInMonth);

        return $firstOfMonth->copy()->day($day);
    }

    /**
     * La fecha límite es el siguiente día de pago ESTRICTAMENTE posterior al
     * corte. Corte 10 / pago 25 vence el 25 del mismo mes; corte 25 / pago 10
     * vence el 10 del mes siguiente.
     */
    private function dueDateFor(Carbon $cut): Carbon
    {
        $firstOfMonth = Carbon::create($cut->year, $cut->month, 1)->startOfDay();
        $candidate = $firstOfMonth->copy()->day(min($this->payment_day, $firstOfMonth->daysInMonth));

        if ($candidate->greaterThan($cut)) {
            return $candidate;
        }

        $next = $cut->copy()->addMonthNoOverflow()->startOfMonth();

        return $next->day(min($this->payment_day, $next->daysInMonth));
    }
}
