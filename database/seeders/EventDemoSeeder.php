<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Eventos de ejemplo (unas vacaciones, un cumpleaños y una escapada) con sus
 * gastos asociados, para poder ver la sección Eventos con datos reales.
 */
class EventDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Evita duplicar si ya existen eventos.
        if (Event::exists()) {
            return;
        }

        $now = Carbon::now();

        $comida = Category::where('name', 'Comida')->first();
        $transporte = Category::where('name', 'Transporte')->first();
        $entretenimiento = Category::where('name', 'Entretenimiento')->first();

        // 1. Unas vacaciones ya terminadas: fechas y presupuesto definidos.
        $vacaciones = Event::create([
            'name' => 'Vacaciones en Puerto Vallarta',
            'description' => 'Una semana en la playa con la familia',
            'starts_at' => $now->copy()->subDays(20)->toDateString(),
            'ends_at' => $now->copy()->subDays(13)->toDateString(),
            'budget' => 12000.00,
            'archived_at' => $now->copy()->subDays(12),
        ]);

        $this->expense($vacaciones, $transporte, 4200.00, 'Vuelos redondos', $now->copy()->subDays(45));
        $this->expense($vacaciones, $transporte, 3800.00, 'Hotel frente al mar', $now->copy()->subDays(21));
        $this->expense($vacaciones, $comida, 1650.00, 'Comidas del viaje', $now->copy()->subDays(16));
        $this->expense($vacaciones, $entretenimiento, 900.00, 'Tour en lancha', $now->copy()->subDays(15));

        // 2. Un cumpleaños próximo: activo, con presupuesto.
        $cumple = Event::create([
            'name' => 'Cumpleaños de Sofía',
            'description' => 'Fiesta sorpresa en casa',
            'starts_at' => $now->copy()->addDays(10)->toDateString(),
            'ends_at' => $now->copy()->addDays(10)->toDateString(),
            'budget' => 4000.00,
        ]);

        $this->expense($cumple, $comida, 850.00, 'Pastel por encargo', $now->copy()->subDays(2));
        $this->expense($cumple, $entretenimiento, 620.00, 'Decoración y globos', $now->copy()->subDays(3));

        // 3. Una escapada improvisada: sin fechas ni presupuesto.
        $sierra = Event::create([
            'name' => 'Escapada a la Sierra',
            'description' => 'Fin de semana improvisado',
        ]);

        $this->expense($sierra, $comida, 420.00, 'Comida en el pueblo', $now->copy()->subDays(6));
        $this->expense($sierra, $transporte, 300.00, 'Gasolina y casetas', $now->copy()->subDays(7));
    }

    private function expense(
        Event $event,
        ?Category $category,
        float $amount,
        string $description,
        Carbon $date
    ): void {
        if (! $category) {
            return;
        }

        Expense::create([
            'category_id' => $category->id,
            'event_id' => $event->id,
            'amount' => $amount,
            'description' => $description,
            'date' => $date,
        ]);
    }
}
