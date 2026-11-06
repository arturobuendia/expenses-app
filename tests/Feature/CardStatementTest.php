<?php

use App\Models\CardStatement;
use App\Models\Expense;
use App\Models\User;

test('el ciclo agrupa las compras desde el corte anterior hasta el corte actual', function () {
    $card = makeCard();

    $cycle = $card->cycleFor('2026-11-05');

    expect($cycle['period_start']->toDateString())->toBe('2026-10-11')
        ->and($cycle['period_end']->toDateString())->toBe('2026-11-10')
        ->and($cycle['cut_date']->toDateString())->toBe('2026-11-10')
        ->and($cycle['due_date']->toDateString())->toBe('2026-11-25');
});

test('una compra después del corte cae en el siguiente ciclo', function () {
    $card = makeCard();

    $cycle = $card->cycleFor('2026-11-12');

    expect($cycle['period_start']->toDateString())->toBe('2026-11-11')
        ->and($cycle['cut_date']->toDateString())->toBe('2026-12-10')
        ->and($cycle['due_date']->toDateString())->toBe('2026-12-25');
});

test('si el día de pago es anterior al corte, vence el mes siguiente', function () {
    $card = makeCard(['cut_off_day' => 25, 'payment_day' => 10]);

    $cycle = $card->cycleFor('2026-11-20');

    expect($cycle['cut_date']->toDateString())->toBe('2026-11-25')
        ->and($cycle['due_date']->toDateString())->toBe('2026-12-10');
});

test('el día 31 se recorta al último día del mes', function () {
    $card = makeCard(['cut_off_day' => 31, 'payment_day' => 31]);

    $cycle = $card->cycleFor('2026-02-15');

    expect($cycle['cut_date']->toDateString())->toBe('2026-02-28')
        ->and($cycle['due_date']->toDateString())->toBe('2026-03-31');
});

test('un cargo con tarjeta se asigna al estado de cuenta de su ciclo', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $card = makeCard();

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 1000,
        'description' => 'Cena',
        'date' => '2026-11-05',
    ])->assertSessionHasNoErrors();

    $expense = Expense::firstOrFail();

    expect($expense->card_statement_id)->not->toBeNull()
        ->and($expense->card_id)->toBe($card->id)
        ->and($expense->statement->cut_date->toDateString())->toBe('2026-11-10')
        ->and($expense->statement->due_date->toDateString())->toBe('2026-11-25');
});

test('la deuda es cargos menos pagos y el cupo disponible es el límite menos la deuda', function () {
    $category = makeCategory('expense');
    $vault = makeVault();
    $card = makeCard(['credit_limit' => 10000]);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 1000,
        'description' => 'Compra',
        'date' => '2026-11-05',
    ]);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 400,
        'description' => 'Otra compra',
        'date' => '2026-11-20',
    ]);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'vault_id' => $vault->id,
        'amount' => 500,
        'description' => 'Pago de la tarjeta',
        'date' => '2026-11-25',
        'is_transfer' => true,
    ]);

    $card = $card->fresh();

    expect($card->currentDebt())->toBe(900.0)
        ->and($card->availableCredit())->toBe(9100.0);
});

test('los pagos liquidan primero el corte más viejo', function () {
    $category = makeCategory('expense');
    $vault = makeVault();
    $card = makeCard(['credit_limit' => 10000]);

    $first = $card->statementFor('2026-10-05');
    $second = $card->statementFor('2026-11-05');

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'card_statement_id' => $first->id,
        'amount' => 1000,
        'description' => 'Compra de octubre',
        'date' => '2026-10-05',
    ]);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'card_statement_id' => $second->id,
        'amount' => 500,
        'description' => 'Compra de noviembre',
        'date' => '2026-11-05',
    ]);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'vault_id' => $vault->id,
        'amount' => 1200,
        'description' => 'Pago parcial',
        'date' => '2026-11-15',
        'is_transfer' => true,
    ]);

    $statements = $card->settledStatements();

    expect($statements[0]->paid_amount)->toBe(1000.0)
        ->and($statements[0]->is_settled)->toBeTrue()
        ->and($statements[1]->paid_amount)->toBe(200.0)
        ->and($statements[1]->pending_amount)->toBe(300.0)
        ->and($statements[1]->is_settled)->toBeFalse();
});

test('un cargo no puede salir de una bóveda y de una tarjeta a la vez', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $vault = makeVault();
    $card = makeCard();

    $response = $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'card_id' => $card->id,
        'amount' => 100,
        'description' => 'Ambiguo',
        'date' => '2026-11-05',
    ]);

    $response->assertSessionHasErrors('card_id');
    expect(Expense::count())->toBe(0);
});

test('dos cargos del mismo ciclo comparten un solo estado de cuenta', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $card = makeCard();

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 500,
        'description' => 'Compra uno',
        'date' => '2026-11-05',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 700,
        'description' => 'Compra dos',
        'date' => '2026-11-08',
    ])->assertSessionHasNoErrors();

    $statements = CardStatement::where('card_id', $card->id)->get();

    expect($statements)->toHaveCount(1)
        ->and(Expense::whereNotNull('card_statement_id')->count())->toBe(2)
        ->and($statements[0]->totalAmount())->toBe(1200.0);
});

test('la página de estados de cuenta muestra los cargos y parcialidades del corte', function () {
    $this->travelTo('2026-11-20');

    $user = User::factory()->create();
    $category = makeCategory('expense');
    $card = makeCard();

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 500,
        'description' => 'Cena',
        'date' => '2026-11-05',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 12000,
        'description' => 'Televisor',
        'date' => '2026-11-05',
        'installment_count' => 12,
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->get('/cards/'.$card->id)
        ->assertInertia(fn ($page) => $page
            ->component('Cards/Show')
            ->where('card.name', 'BBVA Azul')
            ->where('statements', function ($statements) {
                $statement = collect($statements)->first();

                return is_array($statement)
                    && count($statement['charges']) === 1
                    && count($statement['installments']) === 1
                    && (float) $statement['total'] === 1500.0;
            })
        );
});
