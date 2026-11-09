<?php

use App\Models\Expense;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('el filtro de tarjeta muestra solo los gastos de esa tarjeta', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $cardA = makeCard(['name' => 'BBVA Azul']);
    $cardB = makeCard(['name' => 'Amex Oro']);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $cardA->id,
        'amount' => 100,
        'description' => 'Compra con BBVA',
        'date' => now()->toDateString(),
    ]);
    Expense::create([
        'category_id' => $category->id,
        'card_id' => $cardB->id,
        'amount' => 250,
        'description' => 'Compra con Amex',
        'date' => now()->toDateString(),
    ]);
    Expense::create([
        'category_id' => $category->id,
        'card_id' => null,
        'amount' => 50,
        'description' => 'Efectivo',
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get('/expenses?card_id='.$cardA->id)
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.total_amount', fn ($value) => (float) $value === 100.0)
            ->where('totals.transaction_count', 1)
        );
});

test('el filtro de tarjeta con "all" muestra todos los gastos', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $cardA = makeCard(['name' => 'BBVA Azul']);

    Expense::create([
        'category_id' => $category->id,
        'card_id' => $cardA->id,
        'amount' => 100,
        'description' => 'Compra con BBVA',
        'date' => now()->toDateString(),
    ]);
    Expense::create([
        'category_id' => $category->id,
        'card_id' => null,
        'amount' => 50,
        'description' => 'Efectivo',
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get('/expenses?card_id=all')
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.transaction_count', 2)
        );
});
