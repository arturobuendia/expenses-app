<?php

use App\Models\Event;
use App\Models\Expense;
use App\Models\User;

test('asocia un gasto a un evento al registrarlo', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $event = Event::create(['name' => 'Vacaciones']);

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'event_id' => $event->id,
        'amount' => 1500,
        'description' => 'Vuelo',
        'date' => '2026-12-20',
    ])->assertRedirect()->assertSessionHas('success');

    expect(Expense::firstOrFail()->event_id)->toBe($event->id);
});

test('asigna varios gastos existentes a un evento en bloque', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $event = Event::create(['name' => 'Vacaciones']);

    $first = Expense::create([
        'category_id' => $category->id,
        'amount' => 100,
        'description' => 'Hotel',
        'date' => now(),
    ]);
    $second = Expense::create([
        'category_id' => $category->id,
        'amount' => 200,
        'description' => 'Comida',
        'date' => now(),
    ]);

    $this->actingAs($user)->post('/expenses/assign-event', [
        'expense_ids' => [$first->id, $second->id],
        'event_id' => $event->id,
    ])->assertRedirect()->assertSessionHas('success');

    expect($first->fresh()->event_id)->toBe($event->id)
        ->and($second->fresh()->event_id)->toBe($event->id);
});

test('permite registrar gastos posteriores en un evento finalizado', function () {
    $user = User::factory()->create();
    $category = makeCategory('expense');
    $event = Event::create(['name' => 'Vacaciones']);

    Expense::create([
        'category_id' => $category->id,
        'event_id' => $event->id,
        'amount' => 100,
        'description' => 'Hotel',
        'date' => now(),
    ]);

    $this->actingAs($user)->patch("/events/{$event->id}/archive")->assertRedirect();

    $this->actingAs($user)->post('/expenses', [
        'category_id' => $category->id,
        'event_id' => $event->id,
        'amount' => 50,
        'description' => 'Recuerdo',
        'date' => '2027-01-10',
    ])->assertRedirect()->assertSessionHas('success');

    expect($event->fresh()->expenses()->count())->toBe(2);
});
