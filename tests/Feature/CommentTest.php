<?php

use App\Models\Comment;
use App\Models\Project;
use App\Models\ShopDrawing;
use App\Models\Submittal;
use App\Models\User;

test('guests cannot add comments', function () {
    $project = Project::factory()->create();
    $drawing = ShopDrawing::factory()->create(['project_id' => $project->id]);

    $this->post(route('projects.comments.store', $project), [
        'commentable_type' => 'shop-drawing',
        'commentable_id' => $drawing->id,
        'body' => 'Guest comment',
    ])->assertRedirect(route('login'));
});

test('project members can add comments to shop drawings', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);
    $drawing = ShopDrawing::factory()->create(['project_id' => $project->id]);

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), [
            'commentable_type' => 'shop-drawing',
            'commentable_id' => $drawing->id,
            'body' => 'Please revise detail 5',
        ])
        ->assertRedirect();

    expect($drawing->comments)->toHaveCount(1);
    expect($drawing->comments->first()->body)->toBe('Please revise detail 5');
    expect($drawing->comments->first()->user_id)->toBe($user->id);
});

test('project members can add comments to other commentable modules', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);
    $submittal = Submittal::factory()->create(['project_id' => $project->id]);

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), [
            'commentable_type' => 'submittal',
            'commentable_id' => $submittal->id,
            'body' => 'Reviewed the submittal package',
        ])
        ->assertRedirect();

    expect($submittal->comments)->toHaveCount(1);
});

test('users cannot add comments to projects they are not members of', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $drawing = ShopDrawing::factory()->create(['project_id' => $project->id]);

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), [
            'commentable_type' => 'shop-drawing',
            'commentable_id' => $drawing->id,
            'body' => 'Sneaky comment',
        ])
        ->assertForbidden();
});

test('unsupported commentable types are rejected', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), [
            'commentable_type' => 'user',
            'commentable_id' => $project->id,
            'body' => 'Not allowed here',
        ])
        ->assertSessionHasErrors('commentable_type');
});

test('comments cannot reference items from another project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);
    $otherProject = Project::factory()->create();
    $drawing = ShopDrawing::factory()->create(['project_id' => $otherProject->id]);

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), [
            'commentable_type' => 'shop-drawing',
            'commentable_id' => $drawing->id,
            'body' => 'Cross-project sneaky',
        ])
        ->assertNotFound();
});

test('comments require a body', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);
    $drawing = ShopDrawing::factory()->create(['project_id' => $project->id]);

    $this->actingAs($user)
        ->post(route('projects.comments.store', $project), [
            'commentable_type' => 'shop-drawing',
            'commentable_id' => $drawing->id,
        ])
        ->assertSessionHasErrors('body');
});

test('authors can delete their own comments', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);
    $drawing = ShopDrawing::factory()->create(['project_id' => $project->id]);
    $comment = $drawing->addComment('Old comment', $user);

    $this->actingAs($user)
        ->delete(route('projects.comments.destroy', [$project, $comment]))
        ->assertRedirect();

    $this->assertSoftDeleted('comments', ['id' => $comment->id]);
});

test('users cannot delete comments they did not write', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($author, ['project_role' => 'pm']);
    $project->teamMembers()->attach($other, ['project_role' => 'sub']);
    $drawing = ShopDrawing::factory()->create(['project_id' => $project->id]);
    $comment = $drawing->addComment('Author comment', $author);

    $this->actingAs($other)
        ->delete(route('projects.comments.destroy', [$project, $comment]))
        ->assertForbidden();

    expect(Comment::find($comment->id))->not->toBeNull();
});

test('deleting comments requires access to the owning project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);
    $otherProject = Project::factory()->create();
    $owner = User::factory()->create();
    $drawing = ShopDrawing::factory()->create(['project_id' => $otherProject->id]);
    $comment = $drawing->addComment('Other project comment', $owner);

    $this->actingAs($user)
        ->delete(route('projects.comments.destroy', [$project, $comment]))
        ->assertNotFound();

    expect(Comment::find($comment->id))->not->toBeNull();
});
