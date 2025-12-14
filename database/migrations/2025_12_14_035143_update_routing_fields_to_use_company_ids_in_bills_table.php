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
        Schema::table('bills', function (Blueprint $table) {
            // Drop old string columns
            $table->dropColumn(['from_company', 'to_company']);

            // Add foreign key columns
            $table->foreignId('from_company_id')->nullable()->after('customer_info')->constrained('companies')->onDelete('set null');
            $table->foreignId('to_company_id')->nullable()->after('from_company_id')->constrained('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['from_company_id']);
            $table->dropForeign(['to_company_id']);
            $table->dropColumn(['from_company_id', 'to_company_id']);

            // Re-add string columns
            $table->string('from_company')->nullable()->after('customer_info');
            $table->string('to_company')->nullable()->after('from_company');
        });
    }
};
