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
            $table->dateTime('date_time')->nullable();
            $table->foreignId('bill_id')->constrained('bills')->onDelete('cascade');
            $table->decimal('amount', 12, 2);

            // Identity Details
            $table->string('tin_number', 20)->index(); // Indexed for faster searching
            $table->string('customer_name'); // Usually required for LHDN
            $table->string('customer_type'); // e.g., 'Business', 'Individual', 'Foreigner'

            // Contact Info
            $table->string('contact_number', 20)->nullable();
            $table->string('email_address')->nullable();

            // ID Numbers
            $table->string('identity_type')->nullable(); // NRIC, Passport, BRN
            $table->string('customer_ic', 12)->nullable(); // Standardize to 12 chars
            $table->string('business_reg_number', 20)->nullable();
            $table->string('sst_reg_number', 20)->nullable();

            // MSIC Link (The Professional Way)
            // Moved to pivot table e_customer_msic_code

            // Address Details
            $table->text('address'); // Consolidated address column
            $table->string('postcode', 10); // Standardize postcode length
            $table->string('city');
            $table->string('state');
            $table->string('country')->default('Malaysia'); // Changed to match controller input

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
