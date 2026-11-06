<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->renameColumn('debtor_name', 'counterparty_name');
        });

        Schema::table('loans', function (Blueprint $table) {
            // `lent` = me deben, `borrowed` = yo debo. Los registros previos
            // siempre fueron préstamos que el usuario otorgó.
            $table->string('direction')->default('lent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('direction');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->renameColumn('counterparty_name', 'debtor_name');
        });
    }
};
