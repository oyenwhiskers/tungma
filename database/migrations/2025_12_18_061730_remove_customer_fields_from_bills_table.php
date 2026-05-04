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
            if (Schema::hasColumn('bills', 'customer_info')) {
                $table->dropColumn('customer_info');
            }
            if (Schema::hasColumn('bills', 'customer_ic_number')) {
                $table->dropColumn('customer_ic_number');
            }
            if (Schema::hasColumn('bills', 'customer_received_date')) {
                $table->dropColumn('customer_received_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->json('customer_info')->nullable()->after('payment_details');
            $table->string('customer_ic_number')->nullable()->after('customer_info');
            $table->date('customer_received_date')->nullable()->after('customer_ic_number');
        });
    }
};
