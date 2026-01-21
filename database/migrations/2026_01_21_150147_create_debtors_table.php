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
        Schema::create('debtors', function (Blueprint $table) {
            $table->id();
            $table->string('acc_no')->nullable();
            $table->string('company_name')->nullable();
            $table->string('desc2')->nullable();
            $table->string('register_no')->nullable();
            $table->text('address1')->nullable();
            $table->text('address2')->nullable();
            $table->text('address3')->nullable();
            $table->text('address4')->nullable();
            $table->string('post_code')->nullable();
            $table->text('deliver_addr1')->nullable();
            $table->text('deliver_addr2')->nullable();
            $table->text('deliver_addr3')->nullable();
            $table->text('deliver_addr4')->nullable();
            $table->string('deliver_post_code')->nullable();
            $table->string('attention')->nullable();
            $table->string('phone1')->nullable();
            $table->string('phone2')->nullable();
            $table->string('fax1')->nullable();
            $table->string('fax2')->nullable();
            $table->string('area_code')->nullable();
            $table->string('sales_agent')->nullable();
            $table->string('debtor_type')->nullable();
            $table->string('nature_of_business')->nullable();
            $table->string('web_url')->nullable();
            $table->string('email_address')->nullable();
            $table->string('display_term')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->string('aging_on')->nullable();
            $table->string('statement_type')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('allow_exceed_credit_limit')->nullable();
            $table->text('note')->nullable();
            $table->string('exempt_no')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->string('price_category')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('discount_percent', 10, 6)->nullable();
            $table->string('detail_discount')->nullable();
            $table->dateTime('last_modified')->nullable();
            $table->string('last_modified_user_id')->nullable();
            $table->dateTime('created_time_stamp')->nullable();
            $table->string('created_user_id')->nullable();
            $table->decimal('overdue_limit', 15, 2)->nullable();
            $table->boolean('has_bonus_point')->nullable();
            $table->decimal('opening_bonus_point', 15, 2)->nullable();
            $table->string('qt_block_status')->nullable();
            $table->string('so_block_status')->nullable();
            $table->string('do_block_status')->nullable();
            $table->string('iv_block_status')->nullable();
            $table->string('cs_block_status')->nullable();
            $table->text('qt_block_message')->nullable();
            $table->text('so_block_message')->nullable();
            $table->text('do_block_message')->nullable();
            $table->text('iv_block_message')->nullable();
            $table->text('cs_block_message')->nullable();
            $table->string('external_link')->nullable();
            $table->boolean('is_group_company')->nullable();
            $table->boolean('is_active')->nullable();
            $table->integer('last_update')->nullable();
            $table->text('contact_info')->nullable();
            $table->string('account_group')->nullable();
            $table->decimal('markup_ratio', 10, 6)->nullable();
            $table->string('tax_register_no')->nullable();
            $table->decimal('withholding_tax_percent', 10, 6)->nullable();
            $table->string('calc_discount_on_unit_price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debtors');
    }
};
