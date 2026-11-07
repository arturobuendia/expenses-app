<?php

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('el filtro de mes específico muestra solo los gastos de ese mes', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');

    Expense::create([
        'category_id' => $category->id,
        'amount' => 100,
        'description' => 'Marzo 2025',
        'date' => '2025-03-15',
    ]);
    Expense::create([
        'category_id' => $category->id,
        'amount' => 250,
        'description' => 'Mayo 2026',
        'date' => '2026-05-10',
    ]);

    $this->actingAs($user)
        ->get('/expenses?date_filter=2025-03')
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.total_amount', fn ($value) => (float) $value === 100.0)
            ->where('totals.transaction_count', 1)
        );
});

test('el filtro de mes específico muestra solo los ingresos de ese mes', function () {
    $user = User::factory()->create();
    $category = makeCategory('income');
    $vault = makeVault();

    Income::create([
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'amount' => 1000,
        'description' => 'Marzo 2025',
        'date' => '2025-03-15',
    ]);
    Income::create([
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'amount' => 400,
        'description' => 'Mayo 2026',
        'date' => '2026-05-10',
    ]);

    $this->actingAs($user)
        ->get('/incomes?date_filter=2025-03')
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.total_amount', fn ($value) => (float) $value === 1000.0)
            ->where('totals.transaction_count', 1)
        );
});

test('los años disponibles incluyen el año actual y los que tienen datos', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');

    Expense::create([
        'category_id' => $category->id,
        'amount' => 100,
        'description' => 'Marzo 2025',
        'date' => '2025-03-15',
    ]);

    $this->actingAs($user)
        ->get('/expenses')
        ->assertInertia(fn (Assert $page) => $page
            ->where('available_years', fn ($years) => collect($years)->contains(2025)
                && collect($years)->contains((int) now()->year))
        );
});
