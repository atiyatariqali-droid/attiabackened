<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ManageClass;
use App\Models\Session;
use App\Models\Attendance;

class MockDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create 10 Teachers
        $teachers = User::factory()->count(10)->create([
            'role' => 'teacher',
        ]);

        // 2. Create 15 Classes assigned randomly to those teachers
        $classes = collect();
        foreach (range(1, 15) as $i) {
            $classes->push(ManageClass::factory()->create([
                'teacher_id' => $teachers->random()->id,
            ]));
        }

        // 3. Create 100 Students and assign them to these classes
        $students = collect();
        foreach (range(1, 100) as $i) {
            $class = $classes->random();
            $students->push(User::factory()->create([
                'role' => 'student',
                'class_id' => $class->id,
            ]));
        }

        // 4. Generate 20 historical Sessions
        $sessions = collect();
        foreach (range(1, 20) as $i) {
            $class = $classes->random();
            $sessions->push(Session::factory()->create([
                'teacher_id' => $class->teacher_id,
                'class_id' => $class->id,
            ]));
        }

        // 5. Generate Attendance Records
        // For each session, find all students in that class, and mark attendance
        foreach ($sessions as $session) {
            $class = ManageClass::find($session->class_id);
            $classStudents = User::where('role', 'student')->where('class_id', $class->id)->get();

            foreach ($classStudents as $student) {
                Attendance::factory()->create([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'session_id' => $session->id,
                    'attendance_date' => $session->start_time->format('Y-m-d'),
                ]);
            }
        }
    }
}