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
            $table->string('from_company')->nullable()->after('customer_info');
            $table->string('to_company')->nullable()->after('from_company');
            $table->string('sender_name')->nullable()->after('to_company');
            $table->string('sender_phone')->nullable()->after('sender_name');
            $table->string('receiver_name')->nullable()->after('sender_phone');
            $table->string('receiver_phone')->nullable()->after('receiver_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn([
                'from_company',
                'to_company',
                'sender_name',
                'sender_phone',
                'receiver_name',
                'receiver_phone'
            ]);
        });
    }
};
