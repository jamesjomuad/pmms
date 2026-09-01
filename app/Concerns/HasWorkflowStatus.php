<?php

namespace App\Concerns;

use App\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Adds workflow status capability to a model.
 *
 * @method static Builder<Model> whereWorkflowStatus(string $status)
 * @method static Builder<Model> whereWorkflowStatusIn(array<int, WorkflowStatus> $statuses)
 */
trait HasWorkflowStatus
{
    /**
     * Get the status column name.
     */
    public function getWorkflowStatusColumn(): string
    {
        return 'status';
    }

    /**
     * Get the current workflow status.
     */
    public function getWorkflowStatus(): WorkflowStatus
    {
        return WorkflowStatus::from($this->{$this->getWorkflowStatusColumn()});
    }

    /**
     * Set the workflow status.
     */
    public function setWorkflowStatus(WorkflowStatus $status): void
    {
        $this->update([$this->getWorkflowStatusColumn() => $status->value]);
    }

    /**
     * Check if the status matches the given status.
     */
    public function hasWorkflowStatus(WorkflowStatus $status): bool
    {
        return $this->getWorkflowStatus() === $status;
    }

    /**
     * Check if the item is in a reviewable state (pending or in_review).
     */
    public function isReviewable(): bool
    {
        return in_array($this->getWorkflowStatus(), [WorkflowStatus::Pending, WorkflowStatus::InReview]);
    }

    /**
     * Check if the item is editable (draft or revision).
     */
    public function isEditable(): bool
    {
        return in_array($this->getWorkflowStatus(), [WorkflowStatus::Draft, WorkflowStatus::Revision]);
    }

    /**
     * Mark as pending for review.
     */
    public function submitForReview(): void
    {
        $this->setWorkflowStatus(WorkflowStatus::Pending);
    }

    /**
     * Mark as in review.
     */
    public function startReview(): void
    {
        $this->setWorkflowStatus(WorkflowStatus::InReview);
    }

    /**
     * Approve the item.
     */
    public function approveWorkflow(): void
    {
        $this->setWorkflowStatus(WorkflowStatus::Approved);
    }

    /**
     * Reject the item.
     */
    public function rejectWorkflow(): void
    {
        $this->setWorkflowStatus(WorkflowStatus::Rejected);
    }

    /**
     * Request revision.
     */
    public function requestRevision(): void
    {
        $this->setWorkflowStatus(WorkflowStatus::Revision);
    }

    /**
     * Cancel the workflow.
     */
    public function cancelWorkflow(): void
    {
        $this->setWorkflowStatus(WorkflowStatus::Cancelled);
    }

    /**
     * Scope to items with a specific status.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeWorkflowStatus($query, WorkflowStatus $status): Builder
    {
        return $query->where($this->getWorkflowStatusColumn(), $status->value);
    }

    /**
     * Scope to items with any of the given statuses.
     *
     * @param  Builder<Model>  $query
     * @param  array<int, WorkflowStatus>  $statuses
     * @return Builder<Model>
     */
    public function scopeWorkflowStatusIn($query, array $statuses): Builder
    {
        return $query->whereIn(
            $this->getWorkflowStatusColumn(),
            array_map(fn (WorkflowStatus $s) => $s->value, $statuses)
        );
    }
}
