<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('e_customer_msic_code', function (Blueprint $table) {
            $table->id();
            $table->foreignId('e_customer_id')->constrained('e_customers')->onDelete('cascade');
            $table->foreignId('msic_code_id')->constrained('msic_codes')->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate pairs
            $table->unique(['e_customer_id', 'msic_code_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_customer_msic_code');
    }
};
