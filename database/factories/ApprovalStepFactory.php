<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalStep>
 */
class ApprovalStepFactory extends Factory
{
    protected $model = ApprovalStep::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_request_id' => ApprovalRequest::factory(),
            'approver_id' => User::factory(),
            'approver_role' => null,
            'sequence' => 1,
            'status' => 'pending',
            'decided_at' => null,
            'comments' => null,
        ];
    }
}
