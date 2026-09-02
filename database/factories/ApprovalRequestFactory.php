<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\Submittal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approvable_type' => Submittal::class,
            'approvable_id' => 1,
            'requested_by' => User::factory(),
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
