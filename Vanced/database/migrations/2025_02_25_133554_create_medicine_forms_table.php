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
        Schema::create('medicine_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: Tabletas, Suspensión
            $table->string('image')->nullable(); // Ej:  plubli/Capsula.webp | Tabletas.webp
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_forms');
    }
};
