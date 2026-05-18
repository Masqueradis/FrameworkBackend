<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory(),
            'type' => $this->faker->randomElement(['sales', 'inventory', 'users']),
            'filters' => ['start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->toDateString()],
            'status' => ReportStatus::Pending->value,
            'file_path' => null,
        ];
    }
}
