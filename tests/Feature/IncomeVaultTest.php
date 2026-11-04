<?php

use App\Models\Income;
use App\Models\User;

test('registrar un ingreso requiere una bóveda', function () {
    $user = User::factory()->create();
    $category = makeCategory('income');

    $response = $this->actingAs($user)->post('/incomes', [
        'category_id' => $category->id,
        'amount' => 500,
        'description' => 'Sin bóveda',
        'date' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors('vault_id');
    expect(Income::count())->toBe(0);
});

test('registrar un ingreso con bóveda lo deposita ahí', function () {
    $user = User::factory()->create();
    $category = makeCategory('income');
    $vault = makeVault();

    $response = $this->actingAs($user)->post('/incomes', [
        'category_id' => $category->id,
        'vault_id' => $vault->id,
        'amount' => 500,
        'description' => 'Con bóveda',
        'date' => now()->toDateString(),
    ]);

    $response->assertSessionHasNoErrors();
    expect($vault->fresh()->currentBalance())->toBe(1500.0);
});
