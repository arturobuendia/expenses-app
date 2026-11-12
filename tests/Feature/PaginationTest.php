<?php

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function seedExpenses(int $count): void
{
    $category = makeCategory('expense');

    foreach (range(1, $count) as $i) {
        Expense::create([
            'category_id' => $category->id,
            'amount' => 10 * $i,
            'description' => "Gasto {$i}",
            'date' => now()->subDays($i)->toDateString(),
        ]);
    }
}

function seedIncomes(int $count): void
{
    $category = makeCategory('income');
    $vault = makeVault();

    foreach (range(1, $count) as $i) {
        Income::create([
            'category_id' => $category->id,
            'vault_id' => $vault->id,
            'amount' => 100 * $i,
            'description' => "Ingreso {$i}",
            'date' => now()->subDays($i)->toDateString(),
        ]);
    }
}

test('los gastos se paginan de 15 en 15', function () {
    $user = User::factory()->create();
    seedExpenses(20);

    $this->actingAs($user)
        ->get('/expenses?date_filter=all_time')
        ->assertInertia(fn (Assert $page) => $page
            ->has('expenses.data', 15)
            ->where('expenses.current_page', 1)
            ->where('expenses.last_page', 2)
            ->where('expenses.total', 20)
            ->where('expenses.from', 1)
            ->where('expenses.to', 15)
        );

    $this->actingAs($user)
        ->get('/expenses?date_filter=all_time&page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->has('expenses.data', 5)
            ->where('expenses.current_page', 2)
            ->where('expenses.from', 16)
            ->where('expenses.to', 20)
        );
});

test('los ingresos se paginan de 15 en 15', function () {
    $user = User::factory()->create();
    seedIncomes(20);

    $this->actingAs($user)
        ->get('/incomes?date_filter=all_time')
        ->assertInertia(fn (Assert $page) => $page
            ->has('incomes.data', 15)
            ->where('incomes.current_page', 1)
            ->where('incomes.last_page', 2)
            ->where('incomes.total', 20)
        );

    $this->actingAs($user)
        ->get('/incomes?date_filter=all_time&page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->has('incomes.data', 5)
            ->where('incomes.current_page', 2)
        );
});

test('los enlaces de paginación conservan los filtros activos', function () {
    $user = User::factory()->create();
    seedExpenses(20);

    $this->actingAs($user)
        ->get('/expenses?date_filter=all_time&search=Gasto')
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenses.next_page_url', fn (string $url) => str_contains($url, 'date_filter=all_time'))
            ->where('expenses.next_page_url', fn (string $url) => str_contains($url, 'search=Gasto'))
        );
});

test('los totales se calculan sobre todo el conjunto filtrado, no sobre la página', function () {
    $user = User::factory()->create();
    seedExpenses(20);

    $this->actingAs($user)
        ->get('/expenses?date_filter=all_time')
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.transaction_count', 20)
            ->where('totals.total_amount', fn ($value) => (float) $value === 2100.0)
        );
});
