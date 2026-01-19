<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MsicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() {
    $path = database_path('database/data/msic_codes.csv');
    $file = fopen($path, 'r');
    
    while (($data = fgetcsv($file)) !== FALSE) {
        // This is the magic part: it checks the 'code' first
        \App\Models\MsicCode::updateOrCreate(
            ['code' => $data[0]], 
            ['description' => $data[1]]
        );
    }
}
}
