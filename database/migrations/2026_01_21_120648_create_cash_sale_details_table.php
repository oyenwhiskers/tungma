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
        Schema::create('cash_sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_sale_id')->constrained('cash_sales')->onDelete('cascade');
            $table->string('item_code');
            $table->string('uom')->default('UNIT');
            $table->decimal('qty', 10, 6)->default(0);
            $table->decimal('unit_price', 10, 6)->default(0);
            $table->string('location')->nullable();
            $table->string('description')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sale_details');
    }
};
