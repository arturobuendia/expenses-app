<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        $active = Event::withSpent()
            ->active()
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get()
            ->each->withComputedTotals();

        $archived = Event::withSpent()
            ->archived()
            ->orderByDesc('archived_at')
            ->get()
            ->each->withComputedTotals();

        return Inertia::render('Events/Index', [
            'active_events' => $active,
            'archived_events' => $archived,
        ]);
    }

    public function store(Request $request)
    {
        Event::create($this->validatedData($request));

        return redirect()->back()->with('success', 'Evento creado correctamente.');
    }

    public function show(Event $event)
    {
        $event->load([
            'expenses' => fn ($query) => $query
                ->with(['category', 'vault', 'card'])
                ->orderBy('date'),
        ]);

        // Las transferencias mueven saldo pero no son gasto real, igual que en
        // el resto de la app.
        $real = $event->expenses->where('is_transfer', false);

        $byCategory = $real
            ->groupBy('category_id')
            ->map(fn ($group) => [
                'category' => $group->first()->category?->name ?? 'Sin categoría',
                'total' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values();

        $byDay = $real
            ->groupBy(fn (Expense $expense) => $expense->date->toDateString())
            ->map(fn ($group, $day) => [
                'date' => $day,
                'total' => (float) $group->sum('amount'),
            ])
            ->sortKeys()
            ->values();

        $byPaymentMethod = [
            'card' => (float) $real->whereNotNull('card_id')->sum('amount'),
            'vault' => (float) $real->whereNull('card_id')->whereNotNull('vault_id')->sum('amount'),
            'none' => (float) $real->whereNull('card_id')->whereNull('vault_id')->sum('amount'),
        ];

        $event->withComputedTotals();

        return Inertia::render('Events/Show', [
            'event' => $event,
            'by_category' => $byCategory,
            'by_day' => $byDay,
            'by_payment_method' => $byPaymentMethod,
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $event->update($this->validatedData($request));

        return redirect()->back()->with('success', 'Evento actualizado.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->back()->with('success', 'Evento eliminado.');
    }

    public function toggleArchive(Event $event)
    {
        $isArchiving = $event->archived_at === null;

        $event->update(['archived_at' => $isArchiving ? now() : null]);

        return redirect()->back()->with(
            'success',
            $isArchiving ? 'Evento finalizado.' : 'Evento reactivado.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
        ]);

        if (
            ! empty($validated['starts_at'])
            && ! empty($validated['ends_at'])
            && $validated['ends_at'] < $validated['starts_at']
        ) {
            throw ValidationException::withMessages([
                'ends_at' => 'La fecha de fin no puede ser anterior al inicio.',
            ]);
        }

        return $validated;
    }
}
