<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un gasto resta de una bóveda solo cuando se marca explícitamente.
     * is_transfer distingue una transferencia (ej. pago de tarjeta de crédito)
     * de un gasto real: las transferencias mueven saldo pero no cuentan como gasto.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('vault_id')
                ->nullable()
                ->after('category_id')
                ->constrained()
                ->nullOnDelete();

            $table->boolean('is_transfer')->default(false)->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vault_id');
            $table->dropColumn('is_transfer');
        });
    }
};
