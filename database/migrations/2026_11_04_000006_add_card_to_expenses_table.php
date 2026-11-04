<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * card_id identifica la tarjeta del cargo o del pago.
     * card_statement_id solo se llena en cargos; los pagos no pertenecen a un
     * estado de cuenta, lo liquidan.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('card_id')
                ->nullable()
                ->after('vault_id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('card_statement_id')
                ->nullable()
                ->after('card_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('card_statement_id');
            $table->dropConstrainedForeignId('card_id');
        });
    }
};
