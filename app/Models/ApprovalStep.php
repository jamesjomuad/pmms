<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $approval_request_id
 * @property int|null $approver_id
 * @property string|null $approver_role
 * @property int $sequence
 * @property string $status
 * @property Carbon|null $decided_at
 * @property string|null $comments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ApprovalRequest $approvalRequest
 * @property-read User|null $approver
 */
class ApprovalStep extends Model
{
    protected $fillable = [
        'approval_request_id',
        'approver_id',
        'approver_role',
        'sequence',
        'status',
        'decided_at',
        'comments',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    /**
     * Get the parent approval request.
     *
     * @return BelongsTo<ApprovalRequest, $this>
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Get the approver user.
     *
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Approve this step.
     */
    public function approve(?string $comments = null): void
    {
        $this->update([
            'status' => 'approved',
            'decided_at' => now(),
            'comments' => $comments,
        ]);

        $this->approvalRequest->approve();
    }

    /**
     * Reject this step.
     */
    public function reject(?string $comments = null): void
    {
        $this->update([
            'status' => 'rejected',
            'decided_at' => now(),
            'comments' => $comments,
        ]);

        $this->approvalRequest->reject($comments);
    }
}
