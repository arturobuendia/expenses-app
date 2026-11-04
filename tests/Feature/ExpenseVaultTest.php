<?php

use App\Models\Expense;
use App\Models\User;

test('un gasto marcado para la bóveda resta del saldo', function () {
    $vault = makeVault(['initial_balance' => 1000]);
    $category = makeCategory('expense');

    Expense::create([
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'amount' => 300,
        'description' => 'Cena',
        'date' => now(),
    ]);

    expect($vault->fresh()->currentBalance())->toBe(700.0);
});

test('una transferencia resta de la bóveda pero no cuenta como gasto del mes', function () {
    $user = User::factory()->create();
    $vault = makeVault(['initial_balance' => 5000]);
    $category = makeCategory('expense');

    Expense::create([
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'amount' => 1200,
        'description' => 'Pago tarjeta',
        'date' => now(),
        'is_transfer' => true,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('totals.expenses', 0)
        ->where('totals.capital', 3800)
    );
});
