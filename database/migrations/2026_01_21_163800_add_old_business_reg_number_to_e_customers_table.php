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
        Schema::table('e_customers', function (Blueprint $table) {
            $table->string('old_business_reg_number', 20)->nullable()->after('business_reg_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_customers', function (Blueprint $table) {
            $table->dropColumn('old_business_reg_number');
        });
    }
};
