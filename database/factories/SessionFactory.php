<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\ManageClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('-1 month', 'now');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(45, 90) . ' minutes');

        // Simulate locations near the school (Karachi base: 24.8607, 67.0011)
        $lat = 24.8607 + $this->faker->randomFloat(6, -0.001, 0.001);
        $lng = 67.0011 + $this->faker->randomFloat(6, -0.001, 0.001);

        return [
            'teacher_id' => User::where('role', 'teacher')->inRandomOrder()->first()->id ?? User::factory()->create(['role' => 'teacher'])->id,
            'class_id' => ManageClass::inRandomOrder()->first()->id ?? ManageClass::factory()->create()->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'latitude' => $lat,
            'longitude' => $lng,
            'radius' => 150,
            'status' => 'inactive',
        ];
    }
}
