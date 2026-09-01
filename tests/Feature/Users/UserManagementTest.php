<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

/**
 * Helper to attach a user to a shared (non-personal) team and make that team
 * the user's resolved team by removing their personal-team membership.
 *
 * The UserController resolves the acting user's team via `teams()->firstOrFail()`,
 * which is the user's own first team. For role-restriction tests we model the
 * actor as resolving to a shared team where they hold a non-owner role.
 */
function attachToResolvedTeam(User $user, Team $team, TeamRole $role): void
{
    $team->members()->attach($user, ['role' => $role->value]);
    $user->teamMemberships()
        ->where('team_id', $user->personalTeam()->id)
        ->delete();
}

test('users can be listed by the team owner', function () {
    $owner = User::factory()->create();
    $team = $owner->personalTeam();

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->get(route('users.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/Index')
        ->has('users', 2)
        ->has('stats')
        ->where('stats.total', 2)
        ->where('canCreateUser', true)
    );
});

test('team owner can create a new user', function () {
    $owner = User::factory()->create();
    $team = $owner->personalTeam();

    $response = $this
        ->actingAs($owner)
        ->post(route('users.store'), [
            'name' => 'New Tech',
            'email' => 'newtech@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => TeamRole::Member->value,
            'email_verified' => true,
        ]);

    $response->assertRedirect(route('users.index'));

    $newUser = User::where('email', 'newtech@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('New Tech');
    expect($newUser->email_verified_at)->not->toBeNull();
    expect($newUser->belongsToTeam($team))->toBeTrue();
    expect($newUser->teamRole($team))->toBe(TeamRole::Member);
    expect($newUser->personalTeam())->not->toBeNull();
});

test('creating a user requires unique email and valid password', function () {
    $owner = User::factory()->create();
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this
        ->actingAs($owner)
        ->post(route('users.store'), [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertSessionHasErrors(['email', 'password']);
});

test('team owner can update user details and role', function () {
    $owner = User::factory()->create();
    $team = $owner->personalTeam();

    $member = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->patch(route('users.update', $member), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => TeamRole::Admin->value,
        ]);

    $response->assertRedirect(route('users.index'));

    $member->refresh();
    expect($member->name)->toBe('Updated Name');
    expect($member->email)->toBe('updated@example.com');
    expect($member->teamRole($team))->toBe(TeamRole::Admin);
});

test('regular member cannot create a user', function () {
    $member = User::factory()->create();
    $team = Team::factory()->create();
    attachToResolvedTeam($member, $team, TeamRole::Member);

    $response = $this
        ->actingAs($member)
        ->post(route('users.store'), [
            'name' => 'Unauthorized User',
            'email' => 'unauth@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertForbidden();
});

test('admin cannot update team owner', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    attachToResolvedTeam($admin, $team, TeamRole::Admin);

    $response = $this
        ->actingAs($admin)
        ->patch(route('users.update', $owner), [
            'name' => 'Hacked Owner',
            'email' => 'owner@example.com',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertForbidden();
});

test('team owner can remove a user from the team', function () {
    $owner = User::factory()->create();
    $team = $owner->personalTeam();

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('users.destroy', $member));

    $response->assertRedirect(route('users.index'));

    expect($member->fresh()->belongsToTeam($team))->toBeFalse();
});

test('team owner cannot remove themselves', function () {
    $owner = User::factory()->create();
    $team = $owner->personalTeam();

    $response = $this
        ->actingAs($owner)
        ->delete(route('users.destroy', $owner));

    $response->assertForbidden();
    expect($owner->fresh()->belongsToTeam($team))->toBeTrue();
});
