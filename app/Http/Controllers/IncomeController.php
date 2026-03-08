<?php
namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IncomeController extends Controller
{
    public function index()
    {
        // Traemos los ingresos ordenados por fecha, paginados de 15 en 15
        $incomes = Income::with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);

        // Solo categorías activas de tipo "ingreso" para el formulario
        $categories = Category::active()->ofType('income')->get(['id', 'name']);

        return Inertia::render('Incomes/Index', [
            'incomes' => $incomes,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        Income::create($validated);

        return redirect()->back()->with('success', 'Ingreso registrado correctamente.');
    }

    public function update(Request $request, Income $income)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $income->update($validated);

        return redirect()->back()->with('success', 'Ingreso actualizado.');
    }

    public function destroy(Income $income)
    {
        $income->delete();

        return redirect()->back()->with('success', 'Ingreso eliminado.');
    }
}