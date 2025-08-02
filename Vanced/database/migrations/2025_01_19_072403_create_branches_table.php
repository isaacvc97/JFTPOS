<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       // === MIGRACIÓN SUCURSALES ===
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('ruc')->nullable();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
