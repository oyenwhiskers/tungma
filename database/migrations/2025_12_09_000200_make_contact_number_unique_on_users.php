<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'contact_number')) {
                $table->string('contact_number')->unique()->after('email');
                return;
            }

            $table->unique('contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'contact_number')) {
                $table->dropUnique('users_contact_number_unique');
            }
        });
    }
};

