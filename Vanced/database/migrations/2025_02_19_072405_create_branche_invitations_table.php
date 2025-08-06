<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // === MIGRACIÓN INVITACIONES ===
       Schema::create('branch_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('token')->unique();
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente');
            $table->foreignId('enviado_por')->constrained('users');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('branch_invitations');
    }
};
