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
        Schema::create('e_companies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('contact_number', 30)->nullable();
            $table->string('email_address')->nullable();
            $table->text('address')->nullable();
            $table->text('business_activity_description')->nullable();

            $table->string('registration_number', 100)->nullable();
            $table->string('tin_number', 100)->nullable();
            $table->string('sst_registration_number', 100)->nullable();
            $table->string('msic_code', 50)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_companies');
    }

};
