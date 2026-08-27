<?php

namespace App\Http\Controllers;

use App\Actions\Teams\CreateTeam;
use App\Enums\ProjectRole;
use App\Enums\TeamRole;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private CreateTeam $createTeam)
    {
        //
    }

    /**
     * Resolve the team for the authenticated user.
     */
    private function resolveTeam(Request $request): Team
    {
        $user = $request->user();

        return $user->teams()->firstOrFail();
    }

    /**
     * Display a listing of users in the current team.
     */
    public function index(Request $request): Response
    {
        $currentTeam = $this->resolveTeam($request);
        $authUser = $request->user();

        Gate::authorize('viewAny', [User::class, $currentTeam]);

        $teamMembers = $currentTeam->members()
            ->with([
                'teamMemberships' => fn ($q) => $q->where('team_id', $currentTeam->id),
                'projects' => fn ($q) => $q->select('projects.id', 'projects.name', 'projects.project_number', 'projects.status'),
            ])
            ->get();

        $users = $teamMembers->map(function (User $user) use ($authUser, $currentTeam) {
            /** @var TeamRole|null $teamRole */
            $teamRole = $user->teamRole($currentTeam);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'two_factor_enabled' => ! is_null($user->two_factor_confirmed_at),
                'role' => $teamRole ? $teamRole->value : TeamRole::Member->value,
                'role_label' => $teamRole ? $teamRole->label() : TeamRole::Member->label(),
                'created_at' => $user->created_at?->toISOString(),
                'projects_count' => $user->projects->count(),
                'projects' => $user->projects->map(function (Project $project) {
                    $projectRoleValue = $project->pivot->project_role ?? null;
                    $projectRole = $projectRoleValue ? ProjectRole::tryFrom((string) $projectRoleValue) : null;

                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'project_number' => $project->project_number,
                        'status' => $project->status,
                        'project_role' => $projectRoleValue,
                        'project_role_label' => $projectRole?->label() ?? $projectRoleValue,
                    ];
                }),
                'can' => [
                    'update' => $authUser->can('update', [$user, $currentTeam]),
                    'delete' => $authUser->can('delete', [$user, $currentTeam]),
                ],
            ];
        });

        $totalUsers = $teamMembers->count();
        $verifiedUsers = $teamMembers->filter(fn (User $u) => ! is_null($u->email_verified_at))->count();
        $adminsAndOwners = $teamMembers->filter(function (User $u) use ($currentTeam) {
            $role = $u->teamRole($currentTeam);

            return $role === TeamRole::Owner || $role === TeamRole::Admin;
        })->count();
        $twoFactorEnabledCount = $teamMembers->filter(fn (User $u) => ! is_null($u->two_factor_confirmed_at))->count();

        return Inertia::render('users/Index', [
            'users' => $users,
            'stats' => [
                'total' => $totalUsers,
                'verified' => $verifiedUsers,
                'admins_owners' => $adminsAndOwners,
                'two_factor_enabled' => $twoFactorEnabledCount,
            ],
            'availableRoles' => TeamRole::assignable(),
            'canCreateUser' => $authUser->can('create', [User::class, $currentTeam]),
        ]);
    }

    /**
     * Store a newly created user in the current team.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $currentTeam = $this->resolveTeam($request);
        $authUser = $request->user();

        Gate::authorize('create', [User::class, $currentTeam]);

        DB::transaction(function () use ($request, $currentTeam) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
                'email_verified_at' => $request->boolean('email_verified') ? now() : null,
            ]);

            // Create personal team for user fallback
            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            // Attach user to current company/organization team
            $currentTeam->members()->attach($user, [
                'role' => $request->validated('role'),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created successfully.')]);

        return to_route('users.index');
    }

    /**
     * Update the specified user in the current team.
     */
    public function update(UpdateUserRequest $request, string $user): RedirectResponse
    {
        $currentTeam = $this->resolveTeam($request);
        $targetUser = User::findOrFail((int) $user);
        $authUser = $request->user();

        Gate::authorize('update', [$targetUser, $currentTeam]);

        DB::transaction(function () use ($request, $currentTeam, $targetUser) {
            $userData = [
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->validated('password'));
            }

            $targetUser->update($userData);

            // Update team role if not owner
            if (! $targetUser->ownsTeam($currentTeam)) {
                $currentTeam->memberships()
                    ->where('user_id', $targetUser->id)
                    ->update(['role' => TeamRole::from($request->validated('role'))]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated successfully.')]);

        return to_route('users.index');
    }

    /**
     * Remove the specified user from the current team.
     */
    public function destroy(Request $request, string $user): RedirectResponse
    {
        $currentTeam = $this->resolveTeam($request);
        $targetUser = User::findOrFail((int) $user);
        $authUser = $request->user();

        Gate::authorize('delete', [$targetUser, $currentTeam]);

        DB::transaction(function () use ($currentTeam, $targetUser) {
            // Detach user from any project teams in this organization
            $targetUser->projects()->detach();

            // Remove membership from this team
            $currentTeam->memberships()
                ->where('user_id', $targetUser->id)
                ->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User removed from team.')]);

        return to_route('users.index');
    }
}
