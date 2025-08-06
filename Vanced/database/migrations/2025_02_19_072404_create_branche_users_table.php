<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       // === MIGRACIÓN: modificar tabla usuarios ===
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->enum('role', ['administrador', 'vendedor'])->default('vendedor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
