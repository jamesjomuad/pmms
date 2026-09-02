<?php

namespace App\Concerns;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds comment capability to a model.
 *
 * @method MorphMany<Comment, static> comments()
 */
trait HasComments
{
    /**
     * Get all comments for this model.
     *
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Add a comment to this model.
     */
    public function addComment(string $body, User $user): Comment
    {
        /** @var Comment $comment */
        $comment = $this->comments()->create([
            'body' => $body,
            'user_id' => $user->id,
        ]);

        activity()
            ->performedOn($this)
            ->causedBy($user)
            ->event('comment_added')
            ->withProperties(['comment_id' => $comment->id, 'body' => substr($body, 0, 100)])
            ->log('Comment added');

        return $comment;
    }

    /**
     * Get the comment count.
     */
    public function getCommentCount(): int
    {
        return $this->comments()->count();
    }

    /**
     * Remove a comment by ID.
     */
    public function removeComment(int $commentId, User $user): bool
    {
        $comment = $this->comments()->firstWhere('id', $commentId);

        if ($comment && $comment->user_id === $user->id) {
            activity()
                ->performedOn($this)
                ->causedBy($user)
                ->event('comment_removed')
                ->withProperties(['comment_id' => $comment->id])
                ->log('Comment removed');

            return $comment->delete();
        }

        return false;
    }
}
