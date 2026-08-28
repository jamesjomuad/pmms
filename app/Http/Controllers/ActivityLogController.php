<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display the global activity log.
     */
    public function index(Request $request): Response
    {

        /** @var Builder<Activity> $query */
        $query = Activity::query()
            ->with('causer', 'subject')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%");
            });
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        $activities = $query->paginate(50)
            ->through(fn (Activity $activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'event' => $activity->event,
                'log_name' => $activity->log_name,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                ] : null,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'properties' => $activity->properties->toArray(),
                'created_at' => $activity->created_at->toISOString(),
            ]);

        $events = DB::table('activity_log')
            ->select('event')
            ->whereNotNull('event')
            ->distinct()
            ->pluck('event');

        return Inertia::render('activity-log/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'causer_id', 'event']),
            'events' => $events,
        ]);
    }
}
