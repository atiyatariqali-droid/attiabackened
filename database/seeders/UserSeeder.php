<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ─────────────────────────────
        // ADMIN ACCOUNTS
        // ─────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'username' => 'admin',
                'password' => bcrypt('123456'),
                'phone' => '03001234567',
                'role' => 'admin'
            ]
        );

        // ─────────────────────────────
        // TEACHER ACCOUNTS
        // ─────────────────────────────
        User::updateOrCreate(
            ['email' => 'teacher@gmail.com'],
            [
                'username' => 'irha sanaullah',
                'password' => bcrypt('123456'),
                'phone' => '03009876543',
                'role' => 'teacher'
            ]
        );

        User::updateOrCreate(
            ['email' => 'teacher2@gmail.com'],
            [
                'username' => 'Miss Amina',
                'password' => bcrypt('123456'),
                'phone' => '03001122334',
                'role' => 'teacher'
            ]
        );

        User::updateOrCreate(
            ['email' => 'teacher3@gmail.com'],
            [
                'username' => 'Sir Usman',
                'password' => bcrypt('123456'),
                'phone' => '03005566778',
                'role' => 'teacher'
            ]
        );

        // ─────────────────────────────
        // STUDENT ACCOUNTS
        // ─────────────────────────────
        User::updateOrCreate(
            ['email' => 'student@gmail.com'],
            [
                'username' => 'student1',
                'password' => bcrypt('123456'),
                'phone' => '03111234567',
                'role' => 'student'
            ]
        );

        User::updateOrCreate(
            ['email' => 'atiya@gmail.com'],
            [
                'username' => 'Sheeza',
                'password' => bcrypt('123456'),
                'phone' => '03091234567',
                'role' => 'student'
            ]
        );

        User::updateOrCreate(
            ['email' => 'minahil@gmail.com'],
            [
                'username' => 'bisma jabeen',
                'password' => bcrypt('123456'),
                'phone' => '03091934836',
                'role' => 'student'
            ]
        );

        User::updateOrCreate(
            ['email' => 'kinza@gmail.com'],
            [
                'username' => 'Kinza',
                'password' => bcrypt('123456'),
                'phone' => '03012378564',
                'role' => 'student'
            ]
        );

        User::updateOrCreate(
            ['email' => 'nimra@gmail.com'],
            [
                'username' => 'Nimrah',
                'password' => bcrypt('123456'),
                'phone' => '03045623987',
                'role' => 'student'
            ]
        );
    }
}
