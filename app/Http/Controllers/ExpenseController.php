<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index()
    {
        // Traemos los gastos ordenados por fecha, paginados de 15 en 15
        $expenses = Expense::with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);

        // Solo categorías activas de tipo "gasto" para el formulario
        $categories = Category::active()->ofType('expense')->get(['id', 'name']);

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
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

        Expense::create($validated);

        return redirect()->back()->with('success', 'Gasto registrado correctamente.');
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $expense->update($validated);

        return redirect()->back()->with('success', 'Gasto actualizado.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->back()->with('success', 'Gasto eliminado.');
    }
}