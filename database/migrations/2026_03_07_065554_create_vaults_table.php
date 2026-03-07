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
        Schema::create('vaults', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej. Didi, Nu, Efectivo
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('annual_yield_rate', 5, 2)->nullable(); // Ej. 13.00, 15.00
            $table->decimal('yield_cap', 12, 2)->nullable(); // Ej. 10000.00
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaults');
    }
};
