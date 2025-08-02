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
        Schema::create('medicine_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_dosage_id')->constrained()->onDelete('cascade');
            $table->string('unit_type'); // 'fracción', 'blíster', 'caja'
            $table->integer('quantity'); // Ej: 10 tabletas en un blister
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('barcode')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_presentations');
    }
};
