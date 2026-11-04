<?php

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

test('el saldo de la bóveda es el saldo inicial más los ingresos', function () {
    $vault = makeVault(['initial_balance' => 1000]);
    $category = makeCategory('income');

    Income::create([
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'amount' => 2500,
        'description' => 'Proyecto',
        'date' => now(),
    ]);

    expect($vault->fresh()->currentBalance())->toBe(3500.0);
});

test('un gasto sin bóveda no toca el saldo', function () {
    $vault = makeVault(['initial_balance' => 1000]);
    $category = makeCategory('expense');

    Expense::create([
        'category_id' => $category->id,
        'amount' => 300,
        'description' => 'Compra con tarjeta',
        'date' => now(),
    ]);

    expect($vault->fresh()->currentBalance())->toBe(1000.0);
});

test('editar un ingreso recalcula el saldo sin deriva', function () {
    $vault = makeVault(['initial_balance' => 1000]);
    $category = makeCategory('income');

    $income = Income::create([
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'amount' => 1000,
        'description' => 'Proyecto',
        'date' => now(),
    ]);

    expect($vault->fresh()->currentBalance())->toBe(2000.0);

    $income->update(['amount' => 800]);

    expect($vault->fresh()->currentBalance())->toBe(1800.0);
});

test('borrar un ingreso devuelve el saldo al valor anterior', function () {
    $vault = makeVault(['initial_balance' => 1000]);
    $category = makeCategory('income');

    $income = Income::create([
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'amount' => 1000,
        'description' => 'Proyecto',
        'date' => now(),
    ]);

    $income->delete();

    expect($vault->fresh()->currentBalance())->toBe(1000.0);
});

test('el dashboard suma el capital de todas las bóvedas con sus movimientos', function () {
    $user = User::factory()->create();
    $vault = makeVault(['initial_balance' => 1000]);
    $incomeCategory = makeCategory('income');
    $expenseCategory = makeCategory('expense');

    Income::create([
        'category_id' => $incomeCategory->id,
        'vault_id' => $vault->id,
        'amount' => 500,
        'description' => 'Ingreso',
        'date' => now(),
    ]);

    Expense::create([
        'category_id' => $expenseCategory->id,
        'vault_id' => $vault->id,
        'amount' => 200,
        'description' => 'Gasto',
        'date' => now(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('totals.capital', 1300)
        ->where('totals.expenses', 200)
        ->where('totals.incomes', 500)
    );
});
