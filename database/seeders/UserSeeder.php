<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         //admin ky liye
          User::updateOrCreate(

            ['email' => 'admin@gmail.com'],

            [
                'username' => 'admin',
                'password' => bcrypt('123456'),
                'phone' => '03001234567',
                'role' => 'admin'
            ]
        );
  // teacher ky liye
         User::create([
            'username' => 'teacher1',
            'email' => 'teacher1@gmail.com',
            'password' => bcrypt('123456'),
            'phone' => '03001234567',
            'role' => 'teacher'
        ]);
        //student ky liye
        User::create([
            'username' => 'student1',
            'email' => 'student1@gmail.com',
            'password' => bcrypt('123456'),
            'phone' => '03111234567',
            'role' => 'student'
        ]);
    }
}
