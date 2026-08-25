<?php

namespace App\Enums;

enum ProjectRole: string
{
    case ProjectManager = 'pm';
    case FieldTech = 'field_tech';
    case Estimator = 'estimator';
    case Executive = 'exec';
    case Admin = 'admin';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::ProjectManager => 'Project Manager',
            self::FieldTech => 'Field Tech',
            self::Estimator => 'Estimator',
            self::Executive => 'Executive',
            self::Admin => 'Admin',
        };
    }

    /**
     * Get all assignable project roles as value/label pairs.
     *
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
