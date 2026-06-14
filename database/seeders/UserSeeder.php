<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'username'           => 'admin',
                'password'           => bcrypt('123456'),
                'phone'              => '03001234567',
                'role'               => 'admin',
                'status'             => 1,
                'device_mac_address' => 'AA:BB:CC:DD:EE:01',
            ]
        );

        // TEACHERS
        User::updateOrCreate(
            ['email' => 'teacher@gmail.com'],
            [
                'username'           => 'irha sanaullah',
                'password'           => bcrypt('123456'),
                'phone'              => '03009876543',
                'role'               => 'teacher',
                'status'             => 1,
                'device_mac_address' => 'AA:BB:CC:DD:EE:02',
            ]
        );

        User::updateOrCreate(
            ['email' => 'teacher2@gmail.com'],
            [
                'username'           => 'Miss Amina',
                'password'           => bcrypt('123456'),
                'phone'              => '03001122334',
                'role'               => 'teacher',
                'status'             => 1,
                'device_mac_address' => 'AA:BB:CC:DD:EE:03',
            ]
        );

        User::updateOrCreate(
            ['email' => 'teacher3@gmail.com'],
            [
                'username'           => 'Sir Usman',
                'password'           => bcrypt('123456'),
                'phone'              => '03005566778',
                'role'               => 'teacher',
                'status'             => 1,
                'device_mac_address' => 'AA:BB:CC:DD:EE:04',
            ]
        );

        // STUDENTS
        User::updateOrCreate(
            ['email' => 'student@gmail.com'],
            [
                'username'           => 'student1',
                'password'           => bcrypt('123456'),
                'phone'              => '03111234567',
                'role'               => 'student',
                'status'             => 1,
                'class'              => 'Class 5',
                'roll_no'            => '1001',
                'device_mac_address' => 'AA:BB:CC:DD:EE:05',
            ]
        );

        User::updateOrCreate(
            ['email' => 'atiya@gmail.com'],
            [
                'username'           => 'Sheeza',
                'password'           => bcrypt('123456'),
                'phone'              => '03091234567',
                'role'               => 'student',
                'status'             => 1,
                'class'              => 'Class 5',
                'roll_no'            => '1002',
                'device_mac_address' => 'AA:BB:CC:DD:EE:06',
            ]
        );

        User::updateOrCreate(
            ['email' => 'minahil@gmail.com'],
            [
                'username'           => 'bisma jabeen',
                'password'           => bcrypt('123456'),
                'phone'              => '03091934836',
                'role'               => 'student',
                'status'             => 1,
                'class'              => 'Class 5',
                'roll_no'            => '1003',
                'device_mac_address' => 'AA:BB:CC:DD:EE:07',
            ]
        );

        User::updateOrCreate(
            ['email' => 'kinza@gmail.com'],
            [
                'username'           => 'Kinza',
                'password'           => bcrypt('123456'),
                'phone'              => '03012378564',
                'role'               => 'student',
                'status'             => 1,
                'class'              => 'Class 6',
                'roll_no'            => '2001',            // FIX: missing comma added here
                'device_mac_address' => 'AA:BB:CC:DD:EE:08',
            ]
        );

        User::updateOrCreate(
            ['email' => 'nimra@gmail.com'],
            [
                'username'           => 'Nimrah',
                'password'           => bcrypt('123456'),
                'phone'              => '03045623987',
                'role'               => 'student',
                'status'             => 1,
                'class'              => 'Class 6',
                'roll_no'            => '2002',
                'device_mac_address' => 'AA:BB:CC:DD:EE:09',
            ]
        );

        // FIX: changed User::create to updateOrCreate to prevent duplicate errors
        //      if seeder is run more than once
        User::updateOrCreate(
            ['email' => 'teacher1@gmail.com'],
            [
                'username'           => 'teacher1',
                'password'           => bcrypt('123456'),
                'phone'              => '03001234567',
                'role'               => 'teacher',
                'status'             => 1,
                'device_mac_address' => 'AA:BB:CC:DD:EE:10',
            ]
        );

        User::updateOrCreate(
            ['email' => 'student1@gmail.com'],
            [
                'username'           => 'student1',
                'password'           => bcrypt('123456'),
                'phone'              => '03111234567',
                'role'               => 'student',
                'status'             => 1,
                'device_mac_address' => 'AA:BB:CC:DD:EE:11',
            ]
        );
    }
}