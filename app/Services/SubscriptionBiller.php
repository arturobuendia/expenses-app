<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Expense;
use App\Models\Subscription;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Cobra las suscripciones vencidas.
 *
 * El proyecto no tiene scheduler configurado, así que en vez de depender de un
 * cron que nunca corre, el cobro se materializa bajo demanda al abrir el
 * dashboard o la página de suscripciones.
 */
class SubscriptionBiller
{
    /**
     * Tope de seguridad para una suscripción abandonada hace años: evita
     * generar cientos de gastos de golpe.
     */
    private const MAX_PER_RUN = 24;

    public function run(): int
    {
        $created = 0;

        $due = Subscription::query()
            ->where('is_active', true)
            ->whereNotNull('category_id')
            ->whereDate('next_billing_date', '<=', now()->toDateString())
            ->get();

        foreach ($due as $subscription) {
            $created += $this->bill($subscription);
        }

        return $created;
    }

    private function bill(Subscription $subscription): int
    {
        $created = 0;
        $today = now()->startOfDay();

        for ($i = 0; $i < self::MAX_PER_RUN; $i++) {
            $date = $subscription->next_billing_date->copy();

            if ($date->greaterThan($today)) {
                break;
            }

            $alreadyCharged = Expense::query()
                ->where('subscription_id', $subscription->id)
                ->whereDate('date', $date->toDateString())
                ->exists();

            if (! $alreadyCharged) {
                try {
                    Expense::create([
                        'category_id' => $subscription->category_id,
                        'card_id' => $subscription->card_id,
                        'card_statement_id' => $subscription->card_id
                            ? Card::find($subscription->card_id)?->statementFor($date)->id
                            : null,
                        'subscription_id' => $subscription->id,
                        'amount' => $subscription->amount,
                        'description' => $subscription->name,
                        'date' => $date->toDateString(),
                        'is_transfer' => false,
                    ]);

                    $created++;
                } catch (UniqueConstraintViolationException) {
                    // Otro request ya registró este período.
                }
            }

            $subscription->update([
                'next_billing_date' => $this->nextDate($date, $subscription->frequency),
            ]);
        }

        return $created;
    }

    private function nextDate(\Carbon\CarbonInterface $date, string $frequency): \Carbon\CarbonInterface
    {
        return $frequency === 'annual'
            ? $date->copy()->addYearsNoOverflow(1)
            : $date->copy()->addMonthNoOverflow();
    }
}
