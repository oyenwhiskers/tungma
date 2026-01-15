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
        Schema::create('e_customers', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time');
            $table->foreignId('bill_id')->constrained('bills');
            $table->decimal('amount', 12, 2);
            $table->string('tin_number');
            $table->string('customer_name')->nullable();
            $table->string('customer_type');
            $table->string('contact_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('identity_type')->nullable();
            $table->string('customer_ic')->nullable(); // user repeated 9.
            $table->string('business_reg_number')->nullable();
            $table->string('sst_reg_number')->nullable();
            $table->string('address');
            $table->string('postcode');
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_customers');
    }
};
