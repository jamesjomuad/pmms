<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $projectIds = DB::table('project_user')
            ->whereIn('project_id', function ($query) use ($user) {
                $query->select('project_id')
                    ->from('project_user')
                    ->where('user_id', $user->id);
            })
            ->pluck('project_id')
            ->unique();

        $projectStats = Project::query()
            ->whereIn('id', $projectIds)
            ->selectRaw('count(*) as total')
            ->selectRaw("count(*) filter (where status = 'active') as active")
            ->selectRaw("count(*) filter (where status = 'on_hold') as on_hold")
            ->selectRaw("count(*) filter (where status = 'closed') as closed")
            ->first();

        $recentProjects = Project::query()
            ->with('currentStage')
            ->whereIn('id', $projectIds)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
                'project_number' => $project->project_number,
                'status' => $project->status,
                'current_stage' => [
                    'key' => $project->currentStage->key ?? 'unknown',
                    'label' => $project->currentStage->label ?? 'Unknown',
                ],
            ]);

        $recentActivity = DB::table('activity_log')
            ->join('users', 'activity_log.causer_id', '=', 'users.id')
            ->leftJoin('projects', function ($join) {
                $join->on('activity_log.subject_id', '=', 'projects.id')
                    ->where('activity_log.subject_type', '=', 'App\\Models\\Project');
            })
            ->select(
                'activity_log.description',
                'activity_log.event',
                'activity_log.created_at',
                'users.name as causer_name',
                'projects.name as project_name',
                'projects.id as project_id',
            )
            ->orderByDesc('activity_log.created_at')
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'projectStats' => [
                'total' => (int) ($projectStats->total ?? 0),
                'active' => (int) ($projectStats->active ?? 0),
                'on_hold' => (int) ($projectStats->on_hold ?? 0),
                'closed' => (int) ($projectStats->closed ?? 0),
            ],
            'recentProjects' => $recentProjects,
            'recentActivity' => $recentActivity,
        ]);
    }
}
