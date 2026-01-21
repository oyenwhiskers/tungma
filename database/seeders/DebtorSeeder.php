<?php

namespace Database\Seeders;

use App\Models\Debtor;
use Illuminate\Database\Seeder;

class DebtorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Debtor::updateOrCreate(
            ['acc_no' => '300-1000'],
            [
                'company_name' => 'Wawaniaga Sdn Bhd',
                'desc2' => 'Express Bus Company',
                'register_no' => '199601004354',
                'address1' => 'TL.077542735, A Unit Open Shed Light Industrial Building,',
                'address2' => 'Jalan Sentosa Jaya, Batu 3, Jalan Lintas Utara,',
                'address3' => '90000, Sandakan, Sabah',
                'address4' => null,
                'post_code' => '90000',
                'deliver_addr1' => 'TL.077542735, A Unit Open Shed Light Industrial Building,',
                'deliver_addr2' => 'Jalan Sentosa Jaya, Batu 3, Jalan Lintas Utara,',
                'deliver_addr3' => '90000, Sandakan, Sabah',
                'deliver_addr4' => null,
                'deliver_post_code' => '90000',
                'attention' => 'Mr. Stephen Liew',
                'phone1' => '089-218904',
                'phone2' => null,
                'fax1' => null,
                'fax2' => null,
                'area_code' => null,
                'sales_agent' => null,
                'debtor_type' => null,
                'nature_of_business' => null,
                'web_url' => null,
                'email_address' => 'wawaniagasdnbhd@gmail.com',
                'display_term' => 'C.O.D.',
                'credit_limit' => 30000.00,
                'aging_on' => 'Invoice Date',
                'statement_type' => 'Open Item',
                'currency_code' => 'MYR',
                'allow_exceed_credit_limit' => 'T',
                'note' => null, // "null" in XML
                'exempt_no' => null, // "null" in XML
                'expiry_date' => null, // "null" in XML
                'price_category' => null, // "null" in XML
                'tax_type' => null, // "null" in XML
                'discount_percent' => 0.000000,
                'detail_discount' => null, // "null" in XML
                'last_modified' => '2012-03-27T18:02:59.173',
                'last_modified_user_id' => 'ADMIN',
                'created_time_stamp' => '2011-10-07T18:07:23.36',
                'created_user_id' => 'ADMIN',
                'overdue_limit' => 0.00,
                'has_bonus_point' => false,
                'opening_bonus_point' => 0.00,
                'qt_block_status' => '0',
                'so_block_status' => '0',
                'do_block_status' => '0',
                'iv_block_status' => '0',
                'cs_block_status' => '0',
                'qt_block_message' => '',
                'so_block_message' => '',
                'do_block_message' => '',
                'iv_block_message' => '',
                'cs_block_message' => '',
                'external_link' => null, // "null" in XML
                'is_group_company' => false,
                'is_active' => true,
                'last_update' => 1,
                'contact_info' => null, // "null" in XML
                'account_group' => null, // "null" in XML
                'markup_ratio' => null, // "null" in XML
                'tax_register_no' => null, // "null" in XML
                'withholding_tax_percent' => null, // "null" in XML
                'calc_discount_on_unit_price' => null, // "null" in XML
            ]
        );
    }
}
