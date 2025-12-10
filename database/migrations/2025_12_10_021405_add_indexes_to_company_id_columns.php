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
        // Add index to users.company_id for faster lookups
        // Note: foreignId() may already create an index, but we add explicitly for consistency
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('company_id', 'users_company_id_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, skip
        }

        // Add index to bills.company_id for faster filtering
        try {
            Schema::table('bills', function (Blueprint $table) {
                $table->index('company_id', 'bills_company_id_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, skip
        }

        // Add index to courier_policies.company_id for faster filtering
        try {
            Schema::table('courier_policies', function (Blueprint $table) {
                $table->index('company_id', 'courier_policies_company_id_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_company_id_index');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex('bills_company_id_index');
        });

        Schema::table('courier_policies', function (Blueprint $table) {
            $table->dropIndex('courier_policies_company_id_index');
        });
    }
};
