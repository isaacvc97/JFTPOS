<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       // === MIGRACIÓN PIVOTE USUARIO - SUCURSAL ===
        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['administrador', 'vendedor']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
