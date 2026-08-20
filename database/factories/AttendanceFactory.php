<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\ManageClass;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::where('role', 'student')->inRandomOrder()->first()->id ?? User::factory()->create(['role' => 'student'])->id,
            'class_id' => ManageClass::inRandomOrder()->first()->id ?? ManageClass::factory()->create()->id,
            'session_id' => Session::inRandomOrder()->first()->id ?? Session::factory()->create()->id,
            'attendance_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['present', 'absent', 'late']),
        ];
    }
}
