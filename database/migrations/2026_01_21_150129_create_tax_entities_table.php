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
        Schema::create('tax_entities', function (Blueprint $table) {
            $table->id();
            $table->string('identity_type')->nullable();
            $table->string('identity_no')->nullable();
            $table->string('tin')->nullable();
            $table->string('full_tin')->nullable();
            $table->string('tax_category')->nullable();
            $table->string('tax_classification')->nullable();
            $table->string('tax_branch_id')->nullable();
            $table->text('address')->nullable();
            $table->string('post_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email_address')->nullable();
            $table->string('sst_register_no')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('business_activity_desc')->nullable();
            $table->string('msic_code')->nullable();
            $table->string('city')->nullable();
            $table->string('state_code')->nullable();
            $table->string('country_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_entities');
    }
};
