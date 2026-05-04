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
            $table->string('state_code', 2)->nullable()->after('state');
            $table->string('country_code', 3)->default('MYS')->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_customers', function (Blueprint $table) {
            $table->dropColumn(['state_code', 'country_code']);
        });
    }
};
