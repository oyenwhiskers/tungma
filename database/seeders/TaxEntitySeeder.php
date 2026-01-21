<?php

namespace Database\Seeders;

use App\Models\TaxEntity;
use Illuminate\Database\Seeder;

class TaxEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TaxEntity::updateOrCreate(
            ['identity_no' => '199601004354'],
            [
                'identity_type' => 'BRN',
                'tin' => 'C7876068020',
                'full_tin' => 'C7876068020',
                'tax_category' => '1',
                'tax_classification' => '1',
                'tax_branch_id' => '000',
                'address' => 'TL.077542735, A Unit Open Shed Light Industrial Building, Jalan Sentosa Jaya, Batu 3, Jalan Lintas Utara, 90000, Sandakan, Sabah',
                'post_code' => '90000',
                'phone' => '089218904',
                'email_address' => 'wawaniagasdnbhd@gmail.com',
                'sst_register_no' => 'S58-2403-32000001',
                'trade_name' => 'Wawaniaga Sdn Bhd',
                'business_activity_desc' => 'Express Bus Company',
                'msic_code' => '49221',
                'city' => 'Sandakan',
                'state_code' => '12',
                'country_code' => 'MYS',
            ]
        );
    }
}
