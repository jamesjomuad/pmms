<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models in the current team.
     */
    public function viewAny(User $user, ?Team $team = null): bool
    {
        $team = $team ?? $user->currentTeam;

        return $team !== null && $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model, ?Team $team = null): bool
    {
        $team = $team ?? $user->currentTeam;

        return $team !== null && $user->belongsToTeam($team) && $model->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Team $team = null): bool
    {
        $team = $team ?? $user->currentTeam;

        return $team !== null && ($user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model, ?Team $team = null): bool
    {
        $team = $team ?? $user->currentTeam;

        if ($team === null || ! $model->belongsToTeam($team)) {
            return false;
        }

        $userRole = $user->teamRole($team);
        $targetRole = $model->teamRole($team);

        if (! $userRole || ! $targetRole) {
            return false;
        }

        if ($userRole === TeamRole::Owner) {
            return true;
        }

        if ($userRole === TeamRole::Admin && $targetRole !== TeamRole::Owner) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model, ?Team $team = null): bool
    {
        $team = $team ?? $user->currentTeam;

        if ($team === null || ! $model->belongsToTeam($team)) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        if ($model->ownsTeam($team)) {
            return false;
        }

        $userRole = $user->teamRole($team);
        $targetRole = $model->teamRole($team);

        if (! $userRole || ! $targetRole) {
            return false;
        }

        if ($userRole === TeamRole::Owner) {
            return true;
        }

        if ($userRole === TeamRole::Admin && $targetRole === TeamRole::Member) {
            return true;
        }

        return false;
    }
}
