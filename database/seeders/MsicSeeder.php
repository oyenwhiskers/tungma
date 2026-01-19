<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MsicCode;

class MsicSeeder extends Seeder
{
    public function run()
    {
        // Correct path to the CSV file
        $path = database_path('data/msic_codes.csv');

        if (!file_exists($path)) {
            $this->command->error("File not found at: $path");
            return;
        }

        $file = fopen($path, 'r');

        // Skip header row
        fgetcsv($file);

        $count = 0;
        $this->command->info('Start seeding MSIC codes...');

        while (($data = fgetcsv($file)) !== FALSE) {
            // We only want the 5-digit detailed codes
            // Column 0 is 'digits'. We check if it is '5'.
            if (isset($data[0]) && trim($data[0]) == '5') {

                // Column 5 is 'item' (the 5-digit code)
                // Column 6 is 'desc_en' (English description)

                $code = trim($data[5] ?? '');
                $description = trim($data[6] ?? '');

                if (!empty($code) && !empty($description)) {
                    MsicCode::updateOrCreate(
                        ['code' => $code],
                        ['description' => $description]
                    );
                    $count++;
                }
            }
        }

        fclose($file);
        $this->command->info("Seeded $count MSIC codes successfully.");
    }
}
