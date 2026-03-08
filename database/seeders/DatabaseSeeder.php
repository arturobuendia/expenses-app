<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\Vault;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Loan;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        User::create([
            'name' => 'Arturo',
            'email' => 'arturo@apperture.mx',
            'password' => Hash::make('pAsw000rD##'),
        ]);

        $now = Carbon::now();

        // 1. Crear el Catálogo Estricto de Categorías
        $categorias = [
            ['name' => 'Comida', 'type' => 'expense'],
            ['name' => 'Transporte', 'type' => 'expense'],
            ['name' => 'Servidores y Homelab', 'type' => 'expense'],
            ['name' => 'Entretenimiento', 'type' => 'expense'],
            ['name' => 'Educación', 'type' => 'expense'],
            ['name' => 'Proyectos SaaS', 'type' => 'income'],
            ['name' => 'Negocios Locales', 'type' => 'income'],
            ['name' => 'Rendimientos', 'type' => 'income'],
        ];

        foreach ($categorias as $cat) {
            Category::create($cat);
        }

        // Obtener IDs para usarlos en las transacciones
        $catComida = Category::where('name', 'Comida')->first()->id;
        $catServidores = Category::where('name', 'Servidores y Homelab')->first()->id;
        $catEntretenimiento = Category::where('name', 'Entretenimiento')->first()->id;
        $catEducacion = Category::where('name', 'Educación')->first()->id;

        $catSaas = Category::where('name', 'Proyectos SaaS')->first()->id;
        $catNegocios = Category::where('name', 'Negocios Locales')->first()->id;
        $catRendimientos = Category::where('name', 'Rendimientos')->first()->id;

        // 2. Crear las Bóvedas de Capital e Inversión
        Vault::create([
            'name' => 'Cuenta Principal',
            'balance' => 45000.00,
        ]);

        Vault::create([
            'name' => 'Revolut Inversión',
            'balance' => 25000.00,
            'annual_yield_rate' => 15.00,
            'yield_cap' => 25000.00,
        ]);

        Vault::create([
            'name' => 'Didi Rendimientos',
            'balance' => 9500.00,
            'annual_yield_rate' => 13.00,
            'yield_cap' => 10000.00,
        ]);

        // 3. Registrar Ingresos del mes
        Income::create([
            'category_id' => $catSaas,
            'amount' => 12500.00,
            'description' => 'Mensualidades MenuPro',
            'date' => $now->copy()->subDays(5),
        ]);

        Income::create([
            'category_id' => $catSaas,
            'amount' => 8000.00,
            'description' => 'Anticipo desarrollo Nexus Ops',
            'date' => $now->copy()->subDays(10),
        ]);

        Income::create([
            'category_id' => $catNegocios,
            'amount' => 6400.00,
            'description' => 'Ingresos semana Car Spa',
            'date' => $now->copy()->subDays(2),
        ]);

        Income::create([
            'category_id' => $catRendimientos,
            'amount' => 312.50,
            'description' => 'Rendimientos Revolut',
            'date' => $now->copy()->startOfMonth(),
        ]);

        // 4. Registrar Gastos del mes
        Expense::create([
            'category_id' => $catServidores,
            'amount' => 450.00,
            'description' => 'Instancia VPS para Whaas en Coolify',
            'date' => $now->copy()->subDays(12),
        ]);

        Expense::create([
            'category_id' => $catEntretenimiento,
            'amount' => 600.00,
            'description' => 'Noche de Poker',
            'date' => $now->copy()->subDays(8),
        ]);

        Expense::create([
            'category_id' => $catEducacion,
            'amount' => 350.00,
            'description' => 'Libro biográfico en Amazon',
            'date' => $now->copy()->subDays(3),
        ]);

        Expense::create([
            'category_id' => $catEntretenimiento,
            'amount' => 299.00,
            'description' => 'Juego City Builder en Steam',
            'date' => $now->copy()->subDays(1),
        ]);

        Expense::create([
            'category_id' => $catComida,
            'amount' => 850.00,
            'description' => 'Cena de fin de semana',
            'date' => $now->copy()->subDays(4),
        ]);

        // 5. Crear Suscripciones Fijas
        Subscription::create([
            'name' => 'Spotify Premium',
            'amount' => 129.00,
            'next_billing_date' => $now->copy()->addDays(5),
        ]);

        Subscription::create([
            'name' => 'Hetzner Cloud',
            'amount' => 380.00,
            'next_billing_date' => $now->copy()->addDays(12),
        ]);

        Subscription::create([
            'name' => 'Github Copilot',
            'amount' => 200.00,
            'next_billing_date' => $now->copy()->addDays(2),
        ]);

        // 6. Crear Préstamos de prueba
        Loan::create([
            'debtor_name' => 'Luis',
            'amount' => 500.00,
            'description' => 'Para la gasolina',
            'date' => $now->copy()->subDays(2),
            'is_paid' => false,
        ]);

        Loan::create([
            'debtor_name' => 'Carlos',
            'amount' => 1500.00,
            'description' => 'Completar renta',
            'date' => $now->copy()->subDays(15),
            'is_paid' => true, // Este ya te lo pagaron
        ]);

        Loan::create([
            'debtor_name' => 'Ximena',
            'amount' => 800.00,
            'description' => 'Flores proveedor',
            'date' => $now->copy()->subDays(5),
            'is_paid' => false,
        ]);
    }
}
