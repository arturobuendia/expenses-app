<?php

use App\Models\Card;
use App\Models\Expense;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionBiller;

function makeSubscription(array $attributes = []): Subscription
{
    return Subscription::create(array_merge([
        'name' => 'Netflix',
        'amount' => 200,
        'category_id' => makeCategory('expense')->id,
        'next_billing_date' => '2026-11-10',
        'frequency' => 'monthly',
        'is_active' => true,
    ], $attributes));
}

test('una suscripción vencida genera el gasto y avanza el próximo cobro', function () {
    $this->travelTo('2026-11-15');

    $subscription = makeSubscription();

    $created = app(SubscriptionBiller::class)->run();

    expect($created)->toBe(1)
        ->and(Expense::count())->toBe(1)
        ->and($subscription->fresh()->next_billing_date->toDateString())->toBe('2026-12-10');
});

test('una suscripción anual avanza un año', function () {
    $this->travelTo('2026-11-15');

    $subscription = makeSubscription([
        'amount' => 2400,
        'frequency' => 'annual',
        'next_billing_date' => '2026-01-01',
    ]);

    app(SubscriptionBiller::class)->run();

    expect($subscription->fresh()->next_billing_date->toDateString())->toBe('2027-01-01')
        ->and(Expense::count())->toBe(1);
});

test('correr el cobro dos veces no duplica el gasto', function () {
    $this->travelTo('2026-11-15');

    makeSubscription();

    app(SubscriptionBiller::class)->run();
    $second = app(SubscriptionBiller::class)->run();

    expect($second)->toBe(0)
        ->and(Expense::count())->toBe(1);
});

test('una suscripción sin categoría no genera gasto', function () {
    $this->travelTo('2026-11-15');

    makeSubscription(['category_id' => null]);

    $created = app(SubscriptionBiller::class)->run();

    expect($created)->toBe(0)
        ->and(Expense::count())->toBe(0);
});

test('el cobro con tarjeta cae en el corte y suma a la deuda', function () {
    $this->travelTo('2026-11-15');

    $card = Card::create([
        'name' => 'BBVA Azul',
        'credit_limit' => 10000,
        'cut_off_day' => 10,
        'payment_day' => 25,
        'is_active' => true,
    ]);

    makeSubscription(['card_id' => $card->id, 'next_billing_date' => '2026-11-05']);

    app(SubscriptionBiller::class)->run();

    $expense = Expense::firstOrFail();

    expect($expense->card_id)->toBe($card->id)
        ->and($expense->is_transfer)->toBeFalse()
        ->and($expense->card_statement_id)->not->toBeNull()
        ->and($expense->statement->cut_date->toDateString())->toBe('2026-11-10')
        ->and($card->fresh()->currentDebt())->toBe(200.0);
});

test('abrir el dashboard materializa los cobros vencidos', function () {
    $this->travelTo('2026-11-15');

    $user = User::factory()->create();
    makeSubscription();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    expect(Expense::count())->toBe(1);
});

test('el costo mensual divide las anuales entre doce', function () {
    $this->travelTo('2026-11-15');

    $user = User::factory()->create();

    makeSubscription(['amount' => 200, 'next_billing_date' => '2026-12-01']);
    makeSubscription([
        'name' => 'Copilot',
        'amount' => 2400,
        'frequency' => 'annual',
        'next_billing_date' => '2027-01-01',
    ]);

    $this->actingAs($user)->get('/subscriptions')
        ->assertInertia(fn ($page) => $page
            ->where('totals.monthly_cost', fn ($value) => (float) $value === 400.0)
            ->where('totals.annual_projection', fn ($value) => (float) $value === 4800.0)
        );
});
