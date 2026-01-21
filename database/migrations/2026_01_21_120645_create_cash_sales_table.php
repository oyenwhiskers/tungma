<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->nullable()->constrained()->onDelete('set null');
            $table->string('doc_no');
            $table->date('doc_date');
            $table->string('debtor_code')->default('300-W001');
            $table->string('debtor_name')->nullable();
            $table->string('description')->nullable();
            $table->string('display_term')->default('C.O.D.');
            $table->string('ref')->nullable();
            $table->string('sales_agent')->nullable();
            $table->string('import_action')->default('AddUpdate');
            $table->string('xml_status')->default('pending');
            $table->longText('generated_xml')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sales');
    }
};
