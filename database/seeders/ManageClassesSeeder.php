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
                'teacher_id' => 2, // irha sanaullah
                'subject' => 'Islamic Studies',
                'students_count' => 45,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BSIT-2',
                'teacher_id' => 3, // Miss Amina
                'subject' => 'Information Technology',
                'students_count' => 38,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BSENGLISH-3',
                'teacher_id' => 4, // Sir Usman
                'subject' => 'English Literature',
                'students_count' => 30,
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}