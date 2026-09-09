<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\ChangeOrder;
use App\Models\Comment;
use App\Models\EquipmentItem;
use App\Models\Project;
use App\Models\PunchListItem;
use App\Models\Rfi;
use App\Models\ShopDrawing;
use App\Models\Submittal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CommentController extends Controller
{
    /**
     * Store a new comment on a project-scoped resource.
     */
    public function store(StoreCommentRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('view', $project);

        $commentable = $this->resolveCommentable(
            $request->validated('commentable_type'),
            (int) $request->validated('commentable_id'),
            $project,
        );

        $commentable->addComment($request->validated('body'), $request->user()); // @phpstan-ignore method.notFound

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment added.')]);

        return back();
    }

    /**
     * Remove a comment.
     */
    public function destroy(Request $request, Project $project, Comment $comment): RedirectResponse
    {
        Gate::authorize('view', $project);

        $commentable = $comment->commentable;

        if (
            ! $this->isEligibleCommentable($commentable::class)
            || $commentable->getAttribute('project_id') !== $project->id
        ) {
            abort(404);
        }

        if ($comment->user_id !== $request->user()->id) {
            abort(403);
        }

        $commentable->removeComment($comment->id, $request->user()); // @phpstan-ignore method.notFound

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment removed.')]);

        return back();
    }

    /**
     * Resolve the commentable resource, verifying it belongs to the project.
     */
    private function resolveCommentable(string $type, int $id, Project $project): Model
    {
        $model = match ($type) {
            'shop-drawing' => ShopDrawing::class,
            'submittal' => Submittal::class,
            'rfi' => Rfi::class,
            'change-order' => ChangeOrder::class,
            'punch-list-item' => PunchListItem::class,
            'equipment' => EquipmentItem::class,
            default => null,
        };

        if ($model === null) {
            abort(422);
        }

        /** @var Model $commentable */
        $commentable = $model::query()->findOrFail($id);

        if ($commentable->getAttribute('project_id') !== $project->id) {
            abort(404);
        }

        return $commentable;
    }

    /**
     * Verify the commentable model is a supported commentable type.
     */
    private function isEligibleCommentable(string $class): bool
    {
        return in_array($class, [
            ShopDrawing::class,
            Submittal::class,
            Rfi::class,
            ChangeOrder::class,
            PunchListItem::class,
            EquipmentItem::class,
        ], true);
    }
}
