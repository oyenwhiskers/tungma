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
            // Drop existing foreign key constraint
            $table->dropForeign(['bus_departures_id']);

            // Recreate it so that when a bus_departures row is deleted,
            // related bills will have bus_departures_id set to NULL
            $table->foreign('bus_departures_id')
                ->references('id')
                ->on('bus_departures')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            // Drop the updated foreign key
            $table->dropForeign(['bus_departures_id']);

            // Restore original behavior (no ON DELETE action)
            $table->foreign('bus_departures_id')
                ->references('id')
                ->on('bus_departures');
        });
    }
};


