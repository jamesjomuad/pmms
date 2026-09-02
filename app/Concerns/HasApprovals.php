<?php

namespace App\Concerns;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds approval workflow capability to a model.
 *
 * @method MorphMany<ApprovalRequest, static> approvalRequests()
 */
trait HasApprovals
{
    /**
     * Get all approval requests for this model.
     *
     * @return MorphMany<ApprovalRequest, $this>
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    /**
     * Get the latest approval request.
     */
    public function latestApproval(): ?ApprovalRequest
    {
        /** @var ApprovalRequest|null */
        return $this->approvalRequests()->latest()->first();
    }

    /**
     * Check if this model has a pending approval.
     */
    public function hasPendingApproval(): bool
    {
        return $this->approvalRequests()->where('status', 'pending')->exists();
    }

    /**
     * Request approval for this model.
     *
     * @param  User  $requestedBy  The user requesting approval
     * @param  array<int, array{approver_id?: int, approver_role?: string, sequence?: int}>  $steps  Approval steps
     * @param  string|null  $notes  Optional notes
     */
    public function requestApproval(User $requestedBy, array $steps = [], ?string $notes = null): ApprovalRequest
    {
        /** @var ApprovalRequest $request */
        $request = $this->approvalRequests()->create([
            'requested_by' => $requestedBy->id,
            'status' => 'pending',
            'notes' => $notes,
        ]);

        foreach ($steps as $index => $step) {
            $request->steps()->create([
                'approver_id' => $step['approver_id'] ?? null,
                'approver_role' => $step['approver_role'] ?? null,
                'sequence' => $step['sequence'] ?? $index + 1,
                'status' => 'pending',
            ]);
        }

        activity()
            ->performedOn($this)
            ->causedBy($requestedBy)
            ->event('approval_requested')
            ->withProperties([
                'approval_request_id' => $request->id,
                'steps_count' => count($steps),
                'notes' => $notes,
            ])
            ->log('Approval requested');

        return $request;
    }
}
