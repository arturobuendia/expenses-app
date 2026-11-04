<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El saldo de una bóveda deja de ser un dato almacenado y pasa a ser
     * un dato calculado: initial_balance + ingresos - gastos marcados.
     */
    public function up(): void
    {
        Schema::table('vaults', function (Blueprint $table) {
            $table->renameColumn('balance', 'initial_balance');
        });
    }

    public function down(): void
    {
        Schema::table('vaults', function (Blueprint $table) {
            $table->renameColumn('initial_balance', 'balance');
        });
    }
};
