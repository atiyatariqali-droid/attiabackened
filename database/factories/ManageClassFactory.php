<?php

namespace Database\Factories;

use App\Models\ManageClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManageClass>
 */
class ManageClassFactory extends Factory
{
    protected $model = ManageClass::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = ['Computer Science', 'Islamic Studies', 'English Literature', 'Mathematics', 'Physics'];
        $coursePrefixes = ['BSCS', 'BSIT', 'BBA', 'BSENG'];

        return [
            'name' => $this->faker->randomElement($coursePrefixes) . '-' . $this->faker->numberBetween(1, 8),
            'subject' => $this->faker->randomElement($subjects),
            'students_count' => $this->faker->numberBetween(10, 50),
            'status' => $this->faker->randomElement(['active', 'inactive', 'scheduled']),
            // Will be overridden in seeder if needed, or defaults to a random teacher
            'teacher_id' => User::where('role', 'teacher')->inRandomOrder()->first()->id ?? User::factory()->create(['role' => 'teacher'])->id,
        ];
    }
}
