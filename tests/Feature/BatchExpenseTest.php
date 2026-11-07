<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;

test('importa un lote de cargos a una sola tarjeta', function () {
    $user = User::factory()->create();
    $card = makeCard();
    Category::create(['name' => 'Supermercado', 'type' => 'expense', 'is_active' => true]);

    $json = json_encode([
        ['date' => '2026-10-03', 'description' => 'Mercado', 'amount' => 500.50, 'category' => 'Supermercado'],
        ['date' => '2026-10-08', 'description' => 'Lavadora', 'amount' => 7400, 'category' => 'supermercado', 'installment_count' => 12],
    ]);

    $this->actingAs($user)->post('/batch-expenses', [
        'card_id' => $card->id,
        'json' => $json,
    ])->assertRedirect()->assertSessionHas('success');

    expect(Expense::count())->toBe(2);

    // La categoría se resuelve por nombre, sin distinguir mayúsculas.
    $lavadora = Expense::where('description', 'Lavadora')->firstOrFail();
    expect($lavadora->card_id)->toBe($card->id)
        ->and($lavadora->installments)->toHaveCount(12)
        ->and((int) round((float) $lavadora->installments->sum('amount') * 100))->toBe(740000)
        ->and($lavadora->card_statement_id)->toBeNull();

    $mercado = Expense::where('description', 'Mercado')->firstOrFail();
    expect($mercado->card_id)->toBe($card->id)
        ->and($mercado->card_statement_id)->not->toBeNull()
        ->and($mercado->installments)->toHaveCount(0);
});

test('la previsualización resuelve categorías y muestra parcialidades sin persistir', function () {
    $user = User::factory()->create();
    $card = makeCard();
    Category::create(['name' => 'Hogar', 'type' => 'expense', 'is_active' => true]);

    $json = json_encode([
        ['date' => '2026-10-08', 'description' => 'Lavadora', 'amount' => 7400, 'category' => 'Hogar', 'installment_count' => 3],
    ]);

    $this->actingAs($user)->post('/batch-expenses/preview', [
        'card_id' => $card->id,
        'json' => $json,
    ])->assertInertia(fn ($page) => $page
        ->component('BatchExpenses/Index')
        ->where('preview.totals.count', 1)
        ->where('preview.has_errors', false)
        ->where('preview.items.0.category', 'Hogar')
        ->where('preview.items.0.installments', fn ($schedule) => count($schedule) === 3)
    );

    expect(Expense::count())->toBe(0);
});

test('una categoría inexistente se marca como error y no guarda nada', function () {
    $user = User::factory()->create();
    $card = makeCard();

    $json = json_encode([
        ['date' => '2026-10-03', 'description' => 'Mercado', 'amount' => 100, 'category' => 'NoExiste'],
    ]);

    $this->actingAs($user)->post('/batch-expenses', [
        'card_id' => $card->id,
        'json' => $json,
    ])->assertSessionHasErrors('json');

    expect(Expense::count())->toBe(0);
});

test('un JSON mal formado devuelve error de validación', function () {
    $user = User::factory()->create();
    $card = makeCard();

    $this->actingAs($user)->post('/batch-expenses', [
        'card_id' => $card->id,
        'json' => 'esto no es json {',
    ])->assertSessionHasErrors('json');

    expect(Expense::count())->toBe(0);
});

test('el lote exige una tarjeta', function () {
    $user = User::factory()->create();

    $json = json_encode([
        ['date' => '2026-10-03', 'description' => 'Mercado', 'amount' => 100, 'category' => 'Comida'],
    ]);

    $this->actingAs($user)->post('/batch-expenses', [
        'json' => $json,
    ])->assertSessionHasErrors('card_id');
});
