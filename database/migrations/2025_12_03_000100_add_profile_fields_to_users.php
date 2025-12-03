<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'contact_number')) {
                $table->string('contact_number')->nullable();
            }
            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (! Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable();
            }
            if (! Schema::hasColumn('users', 'ic_number')) {
                $table->string('ic_number')->nullable();
            }
            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable();
            }
            if (! Schema::hasColumn('users', 'company_id')) {
                // Add company_id now without FK to avoid order issues; FK can be added later
                $table->foreignId('company_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['super_admin', 'admin', 'staff'])->default('staff');
            }
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('users', 'company_id')) {
                $table->dropColumn('company_id');
            }
            $columns = ['username', 'contact_number', 'date_of_birth', 'gender', 'ic_number', 'position', 'role'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
