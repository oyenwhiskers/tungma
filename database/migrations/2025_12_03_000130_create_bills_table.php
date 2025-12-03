<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_code')->unique();
            $table->date('date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('payment_details')->nullable();
            $table->json('customer_info')->nullable();
            $table->foreignId('courier_policy_id')->nullable()->constrained('courier_policies');
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->string('eta')->nullable();
            $table->json('sst_details')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
