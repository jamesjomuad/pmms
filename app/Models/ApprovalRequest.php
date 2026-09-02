<?php

namespace App\Models;

use Database\Factories\ApprovalRequestFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property int $requested_by
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Model $approvable
 * @property-read User $requester
 * @property-read Collection<int, ApprovalStep> $steps
 */
class ApprovalRequest extends Model
{
    /** @use HasFactory<ApprovalRequestFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'requested_by',
        'status',
        'notes',
    ];

    /**
     * Get the parent approvable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who requested the approval.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the approval steps for this request.
     *
     * @return HasMany<ApprovalStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('sequence');
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the request if all steps are approved.
     */
    public function approve(): void
    {
        if ($this->steps()->where('status', 'pending')->exists()) {
            return;
        }

        $allApproved = $this->steps()->where('status', '!=', 'approved')->doesntExist();

        $this->update(['status' => $allApproved ? 'approved' : 'rejected']);
    }

    /**
     * Reject the request.
     */
    public function reject(?string $reason = null): void
    {
        $this->update(['status' => 'rejected', 'notes' => $reason]);
        $this->steps()->where('status', 'pending')->update(['status' => 'skipped']);
    }

    /**
     * Cancel the request.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
        $this->steps()->where('status', 'pending')->update(['status' => 'skipped']);
    }
}
