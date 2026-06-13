<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettings extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'school_name',
                'value' => 'ABC Public School',
                'description' => 'School Name',
                'type' => 'string',
            ],
            [
                'key' => 'school_address',
                'value' => 'Main Road, Karachi, Pakistan',
                'description' => 'School Address',
                'type' => 'string',
            ],
            [
                'key' => 'school_contact',
                'value' => '+92 300 1234567',
                'description' => 'School Contact Number',
                'type' => 'string',
            ],
            [
                'key' => 'school_latitude',
                'value' => '24.8607',
                'description' => 'School Latitude',
                'type' => 'decimal',
            ],
            [
                'key' => 'school_longitude',
                'value' => '67.0011',
                'description' => 'School Longitude',
                'type' => 'decimal',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}