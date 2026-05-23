<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManageClassesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                DB::table('manage_classes')->insert([

            [
                'name' => 'BSISLAMIYAT-1',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'BSIT-2',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'BSENGLISH-3',
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ]

        ]);
    }
}
