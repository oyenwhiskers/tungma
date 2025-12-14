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
            $table->boolean('is_paid')->default(false)->after('amount');
            $table->foreignId('created_by')->nullable()->constrained('users')->after('company_id');
            $table->foreignId('checked_by')->nullable()->constrained('users')->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['checked_by']);
            $table->dropColumn(['is_paid', 'created_by', 'checked_by']);
        });
    }
};
