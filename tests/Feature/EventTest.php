<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Expense;
use App\Models\User;

test('crea un evento con fechas y presupuesto opcionales', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/events', [
        'name' => 'Vacaciones en la playa',
        'description' => 'Viaje con la familia',
        'starts_at' => '2026-12-20',
        'ends_at' => '2026-12-27',
        'budget' => 5000,
    ])->assertRedirect()->assertSessionHas('success');

    $event = Event::firstOrFail();

    expect($event->name)->toBe('Vacaciones en la playa')
        ->and((float) $event->budget)->toBe(5000.0)
        ->and($event->archived_at)->toBeNull();
});

test('un evento puede crearse sin fechas ni presupuesto', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/events', [
        'name' => 'Regalo de cumpleaños',
        'starts_at' => null,
        'ends_at' => null,
        'budget' => null,
    ])->assertRedirect()->assertSessionHas('success');

    $event = Event::firstOrFail();

    expect($event->starts_at)->toBeNull()
        ->and($event->ends_at)->toBeNull()
        ->and($event->budget)->toBeNull();
});

test('finalizar un evento conserva sus gastos', function () {
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

    $event->refresh();

    expect($event->archived_at)->not->toBeNull()
        ->and($event->expenses()->count())->toBe(1);
});

test('el listado separa eventos activos de finalizados', function () {
    $user = User::factory()->create();
    Event::create(['name' => 'Activo']);
    Event::create(['name' => 'Pasado', 'archived_at' => now()]);

    $this->actingAs($user)->get('/events')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->where('active_events.0.name', 'Activo')
            ->where('archived_events.0.name', 'Pasado')
            ->where('archived_events.0.is_archived', true)
        );
});

test('el detalle del evento calcula totales y distribución', function () {
    $user = User::factory()->create();
    $food = Category::create(['name' => 'Comida', 'type' => 'expense', 'is_active' => true]);
    $transport = Category::create(['name' => 'Transporte', 'type' => 'expense', 'is_active' => true]);
    $event = Event::create(['name' => 'Vacaciones', 'budget' => 1000]);

    Expense::create([
        'category_id' => $food->id,
        'event_id' => $event->id,
        'amount' => 300,
        'description' => 'Comida',
        'date' => '2026-12-20',
    ]);
    Expense::create([
        'category_id' => $transport->id,
        'event_id' => $event->id,
        'amount' => 200,
        'description' => 'Taxi',
        'date' => '2026-12-20',
    ]);

    $this->actingAs($user)->get("/events/{$event->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.spent', 500)
            ->where('event.remaining_budget', 500)
            ->where('by_category.0.total', 300)
            ->where('by_day.0.total', 500)
        );
});
