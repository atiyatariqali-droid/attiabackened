<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemConfi;

class SystemConfiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemConfi::create([
            'longitude' => 12.3456,
            'latitude' => 78.9012,
            'school_name' => 'ABC School',
            'school_address' => 'Gujranwala, Punjanb, Pakistan',
            'school_contact' => '03111234567'
        ]);
    }
}
