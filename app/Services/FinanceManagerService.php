<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use Carbon\Carbon;

class FinanceManagerService
{
    public function recordTransaction(array $data)
    {
        $data['date'] = Carbon::now()->toDateString();

        if ($data['type'] === 'expense') {
            return Expense::create([
                'category_id' => $data['category_id'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'date' => $data['date'],
            ]);
        }

        if ($data['type'] === 'income') {
            return Income::create([
                'category_id' => $data['category_id'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'date' => $data['date'],
            ]);
        }

        throw new \Exception('Tipo de transacción no válido');
    }
}