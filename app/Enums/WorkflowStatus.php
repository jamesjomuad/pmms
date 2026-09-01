<?php

namespace App\Enums;

enum WorkflowStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revision = 'revision';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Revision => 'Revision Required',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get the color variant for badges.
     */
    public function variant(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Pending => 'outline',
            self::InReview => 'default',
            self::Approved => 'default',
            self::Rejected => 'destructive',
            self::Revision => 'secondary',
            self::Cancelled => 'secondary',
        };
    }

    /**
     * Get all statuses as value/label pairs.
     *
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()])
            ->values()
            ->toArray();
    }
}
