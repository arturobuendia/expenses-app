<?php

use App\Models\Loan;
use App\Models\User;

function makeLoan(array $attributes = []): Loan
{
    return Loan::create(array_merge([
        'counterparty_name' => 'Luis',
        'direction' => 'lent',
        'amount' => 500,
        'description' => 'Gasolina',
        'date' => now()->toDateString(),
        'is_paid' => false,
    ], $attributes));
}

test('se registra un préstamo que te deben como cuenta por cobrar', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/loans', [
        'counterparty_name' => 'Luis',
        'direction' => 'lent',
        'amount' => 500,
        'description' => 'Gasolina',
        'date' => now()->toDateString(),
    ]);

    $response->assertRedirect();

    $loan = Loan::firstOrFail();

    expect($loan->counterparty_name)->toBe('Luis')
        ->and($loan->direction)->toBe('lent')
        ->and((float) $loan->amount)->toBe(500.0);
});

test('se registra un préstamo que debes como cuenta por pagar', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/loans', [
        'counterparty_name' => 'Sofía',
        'direction' => 'borrowed',
        'amount' => 2000,
        'description' => 'Préstamo para la boda',
        'date' => now()->toDateString(),
    ]);

    $response->assertRedirect();

    expect(Loan::firstOrFail()->direction)->toBe('borrowed');
});

test('la dirección debe ser válida', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/loans', [
        'counterparty_name' => 'Luis',
        'direction' => 'unknown',
        'amount' => 500,
        'date' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors('direction');
    expect(Loan::count())->toBe(0);
});

test('los totales separan lo que te deben de lo que debes', function () {
    $user = User::factory()->create();

    makeLoan(['direction' => 'lent', 'amount' => 500]);
    makeLoan(['direction' => 'borrowed', 'amount' => 800]);
    makeLoan(['direction' => 'lent', 'amount' => 300, 'is_paid' => true]);
    makeLoan(['direction' => 'borrowed', 'amount' => 200, 'is_paid' => true]);

    $response = $this->actingAs($user)->get('/loans');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('totals.receivable_pending_amount', 500)
        ->where('totals.receivable_pending_count', 1)
        ->where('totals.payable_pending_amount', 800)
        ->where('totals.payable_pending_count', 1)
        ->where('totals.receivable_settled_amount', 300)
        ->where('totals.receivable_settled_count', 1)
        ->where('totals.payable_settled_amount', 200)
        ->where('totals.payable_settled_count', 1)
    );
});

test('el dashboard separa lo que te deben de lo que debes', function () {
    $user = User::factory()->create();

    makeLoan(['direction' => 'lent', 'amount' => 500]);
    makeLoan(['direction' => 'borrowed', 'amount' => 800]);
    makeLoan(['direction' => 'borrowed', 'amount' => 400, 'is_paid' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('totals.pending_loans', 500)
        ->where('totals.pending_debts', 800)
    );
});

test('marcar como pagado liquida el préstamo sin importar la dirección', function () {
    $user = User::factory()->create();
    $loan = makeLoan(['direction' => 'borrowed', 'amount' => 800]);

    $response = $this->actingAs($user)->patch("/loans/{$loan->id}/pay");

    $response->assertRedirect();
    expect($loan->fresh()->is_paid)->toBeTrue();
});

test('un abono reduce el saldo pendiente y los totales', function () {
    $user = User::factory()->create();
    $loan = makeLoan(['direction' => 'lent', 'amount' => 500]);

    $this->actingAs($user)->post("/loans/{$loan->id}/payments", [
        'amount' => 200,
        'date' => now()->toDateString(),
    ])->assertRedirect();

    $fresh = $loan->fresh();

    expect($fresh->payments()->count())->toBe(1)
        ->and($fresh->remainingAmount())->toBe(300.0)
        ->and($fresh->isSettled())->toBeFalse();

    $this->actingAs($user)->get('/loans')->assertInertia(
        fn ($page) => $page->where('totals.receivable_pending_amount', 300)
    );
});

test('los abonos se acumulan y liquidan el préstamo al cubrir el monto', function () {
    $user = User::factory()->create();
    $loan = makeLoan(['direction' => 'lent', 'amount' => 500]);

    $this->actingAs($user)->post("/loans/{$loan->id}/payments", [
        'amount' => 300,
        'date' => now()->toDateString(),
    ])->assertRedirect();

    $this->actingAs($user)->post("/loans/{$loan->id}/payments", [
        'amount' => 200,
        'date' => now()->toDateString(),
    ])->assertRedirect();

    $fresh = $loan->fresh();

    expect($fresh->remainingAmount())->toBe(0.0)
        ->and($fresh->isSettled())->toBeTrue();
});

test('un abono no puede exceder el saldo pendiente', function () {
    $user = User::factory()->create();
    $loan = makeLoan(['direction' => 'lent', 'amount' => 500]);

    $this->actingAs($user)->post("/loans/{$loan->id}/payments", [
        'amount' => 600,
        'date' => now()->toDateString(),
    ])->assertSessionHasErrors('amount');

    expect($loan->fresh()->payments()->count())->toBe(0);
});

test('se puede eliminar un abono', function () {
    $user = User::factory()->create();
    $loan = makeLoan(['direction' => 'lent', 'amount' => 500]);

    $this->actingAs($user)->post("/loans/{$loan->id}/payments", [
        'amount' => 200,
        'date' => now()->toDateString(),
    ])->assertRedirect();

    $payment = $loan->fresh()->payments()->first();

    $this->actingAs($user)->delete("/loans/{$loan->id}/payments/{$payment->id}")
        ->assertRedirect();

    expect($loan->fresh()->payments()->count())->toBe(0)
        ->and($loan->fresh()->remainingAmount())->toBe(500.0);
});
