<?php

use App\Models\Expense;
use App\Models\User;

test('una compra a doce meses genera doce parcialidades que suman el total', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $card = makeCard();

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 12000,
        'description' => 'Televisor',
        'date' => '2026-11-05',
        'installment_count' => 12,
    ])->assertSessionHasNoErrors();

    $expense = Expense::with('installments')->firstOrFail();

    expect($expense->installments)->toHaveCount(12)
        ->and((float) $expense->installments->sum('amount'))->toBe(12000.0)
        ->and($expense->installments->first()->cut_date->toDateString())->toBe('2026-11-10')
        ->and($expense->installments->last()->cut_date->toDateString())->toBe('2027-10-10')
        // Una compra diferida no vive en un solo estado de cuenta.
        ->and($expense->card_statement_id)->toBeNull();
});

test('el residuo de la división cae en la última parcialidad', function () {
    $category = makeCategory('expense');
    $card = makeCard();

    $expense = Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 1000,
        'description' => 'Compra chica',
        'date' => '2026-11-05',
        'installment_count' => 3,
    ]);

    $expense->syncInstallments();

    $amounts = $expense->installments()->pluck('amount')->map(fn ($value) => (float) $value)->all();

    expect($amounts)->toBe([333.33, 333.33, 333.34]);
});

test('el estado de cuenta solo factura la parcialidad del corte', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $card = makeCard();

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 12000,
        'description' => 'Televisor',
        'date' => '2026-11-05',
        'installment_count' => 12,
    ])->assertSessionHasNoErrors();

    $statement = $card->statementFor('2026-11-10');

    expect($statement->totalAmount())->toBe(1000.0);
});

test('un ciclo con parcialidades aparece aunque no tenga cargos directos', function () {
    $this->travelTo('2026-11-20');

    $category = makeCategory('expense');
    $card = makeCard();

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 12000,
        'description' => 'Televisor',
        'date' => '2026-11-05',
        'installment_count' => 12,
    ])->syncInstallments();

    $statements = $card->settledStatements();

    expect($statements)->toHaveCount(1)
        ->and($statements[0]->cut_date->toDateString())->toBe('2026-11-10')
        ->and($statements[0]->totalAmount())->toBe(1000.0)
        ->and($statements[0]->pending_amount)->toBe(1000.0);
});

test('el dashboard cuenta la parcialidad, no la compra completa', function () {
    $this->travelTo('2026-11-20');

    $user = User::factory()->create();
    $category = makeCategory('expense');
    $card = makeCard();

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 12000,
        'description' => 'Televisor',
        'date' => '2026-11-05',
        'installment_count' => 12,
    ])->syncInstallments();

    $this->actingAs($user)->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('totals.expenses', fn ($value) => (float) $value === 1000.0)
        );
});

test('la deuda incluye la compra completa desde el primer día', function () {
    $category = makeCategory('expense');
    $card = makeCard(['credit_limit' => 40000]);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 12000,
        'description' => 'Televisor',
        'date' => '2026-11-05',
        'installment_count' => 12,
    ])->syncInstallments();

    expect($card->fresh()->currentDebt())->toBe(12000.0)
        ->and($card->fresh()->availableCredit())->toBe(28000.0);
});

test('editar la compra regenera las parcialidades', function () {
    $category = makeCategory('expense');
    $card = makeCard(['credit_limit' => 40000]);

    $expense = Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 12000,
        'description' => 'Televisor',
        'date' => '2026-11-05',
        'installment_count' => 12,
    ]);

    $expense->syncInstallments();
    expect($expense->installments()->count())->toBe(12);

    $expense->update(['installment_count' => 6, 'amount' => 6000]);
    $expense->syncInstallments();

    expect($expense->installments()->count())->toBe(6)
        ->and((float) $expense->installments()->sum('amount'))->toBe(6000.0);
});

test('los meses sin intereses exigen una tarjeta', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');

    $response = $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'amount' => 1000,
        'description' => 'Sin tarjeta',
        'date' => '2026-11-05',
        'installment_count' => 6,
    ]);

    $response->assertSessionHasErrors('card_id');
    expect(Expense::count())->toBe(0);
});

test('una transferencia no se puede diferir a meses', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $card = makeCard();
    $vault = makeVault();

    $response = $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'card_id' => $card->id,
        'vault_id' => $vault->id,
        'amount' => 200,
        'description' => 'Pago',
        'date' => '2026-11-05',
        'is_transfer' => true,
        'installment_count' => 3,
    ]);

    $response->assertSessionHasErrors('installment_count');
    expect(Expense::count())->toBe(0);
});

test('un gasto del último día del mes sí cuenta en el dashboard', function () {
    $this->travelTo('2026-11-30');

    $user = User::factory()->create();
    $category = makeCategory('expense');

    Expense::create([
        'category_id' => $category->id,
        'amount' => 100,
        'description' => 'Compra de cierre de mes',
        'date' => '2026-11-30',
    ]);

    $this->actingAs($user)->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('totals.expenses', fn ($value) => (float) $value === 100.0)
        );
});

test('una parcialidad que corta el último día del mes sí cuenta', function () {
    $this->travelTo('2026-11-30');

    $user = User::factory()->create();
    $category = makeCategory('expense');
    // Corte el 31: en noviembre el corte cae el día 30.
    $card = makeCard(['cut_off_day' => 31, 'payment_day' => 31, 'credit_limit' => 40000]);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $card->id,
        'amount' => 12000,
        'description' => 'Televisor',
        'date' => '2026-11-05',
        'installment_count' => 12,
    ])->syncInstallments();

    $this->actingAs($user)->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('totals.expenses', fn ($value) => (float) $value === 1000.0)
        );
});
