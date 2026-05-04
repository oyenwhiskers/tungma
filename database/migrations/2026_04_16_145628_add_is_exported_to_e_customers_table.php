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
            if (!Schema::hasColumn('e_customers', 'is_exported')) {
                $table->boolean('is_exported')->default(false)->after('amount');
            }
            if (!Schema::hasColumn('e_customers', 'is_processed')) {
                $table->boolean('is_processed')->default(false)->after('is_exported');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_customers', function (Blueprint $table) {
            $table->dropColumn(['is_exported', 'is_processed']);
        });
    }
};
