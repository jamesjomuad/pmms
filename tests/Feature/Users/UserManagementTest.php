<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('users can be listed by team members', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->update(['current_team_id' => $team->id]);

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $response = $this
        ->actingAs($owner)
        ->get(route('users.index', $team));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/Index')
        ->has('users', 2)
        ->has('stats')
        ->where('stats.total', 2)
        ->where('canCreateUser', true)
    );
});

test('users cannot be listed by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $response = $this
        ->actingAs($nonMember)
        ->get(route('users.index', $team));

    $response->assertForbidden();
});

test('team owner can create a new user', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->update(['current_team_id' => $team->id]);

    $response = $this
        ->actingAs($owner)
        ->post(route('users.store', $team), [
            'name' => 'New Tech',
            'email' => 'newtech@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => TeamRole::Member->value,
            'email_verified' => true,
        ]);

    $response->assertRedirect(route('users.index', $team));

    $newUser = User::where('email', 'newtech@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('New Tech');
    expect($newUser->email_verified_at)->not->toBeNull();
    expect($newUser->belongsToTeam($team))->toBeTrue();
    expect($newUser->teamRole($team))->toBe(TeamRole::Member);
    expect($newUser->personalTeam())->not->toBeNull();
});

test('team admin can create a new member', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $admin->update(['current_team_id' => $team->id]);

    $response = $this
        ->actingAs($admin)
        ->post(route('users.store', $team), [
            'name' => 'Field Worker',
            'email' => 'worker@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => TeamRole::Member->value,
            'email_verified' => false,
        ]);

    $response->assertRedirect(route('users.index', $team));

    $newUser = User::where('email', 'worker@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->email_verified_at)->toBeNull();
    expect($newUser->belongsToTeam($team))->toBeTrue();
});

test('regular member cannot create a user', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->update(['current_team_id' => $team->id]);

    $response = $this
        ->actingAs($member)
        ->post(route('users.store', $team), [
            'name' => 'Unauthorized User',
            'email' => 'unauth@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertForbidden();
});

test('creating a user requires unique email and valid password', function () {
    $owner = User::factory()->create();
    $existing = User::factory()->create(['email' => 'existing@example.com']);
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->update(['current_team_id' => $team->id]);

    $response = $this
        ->actingAs($owner)
        ->post(route('users.store', $team), [
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
    $member = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $owner->update(['current_team_id' => $team->id]);

    $response = $this
        ->actingAs($owner)
        ->patch(route('users.update', [$team, $member]), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => TeamRole::Admin->value,
        ]);

    $response->assertRedirect(route('users.index', $team));

    $member->refresh();
    expect($member->name)->toBe('Updated Name');
    expect($member->email)->toBe('updated@example.com');
    expect($member->teamRole($team))->toBe(TeamRole::Admin);
});

test('admin cannot update team owner', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $admin->update(['current_team_id' => $team->id]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('users.update', [$team, $owner]), [
            'name' => 'Hacked Owner',
            'email' => 'owner@example.com',
            'role' => TeamRole::Member->value,
        ]);

    $response->assertForbidden();
});

test('team owner can remove a user from the team', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $personalTeam = $member->personalTeam();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $owner->update(['current_team_id' => $team->id]);
    $member->update(['current_team_id' => $team->id]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('users.destroy', [$team, $member]));

    $response->assertRedirect(route('users.index', $team));

    expect($member->fresh()->belongsToTeam($team))->toBeFalse();
    if ($personalTeam) {
        expect($member->fresh()->current_team_id)->toBe($personalTeam->id);
    }
});

test('cannot remove team owner', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->update(['current_team_id' => $team->id]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('users.destroy', [$team, $owner]));

    $response->assertForbidden();
    expect($owner->fresh()->belongsToTeam($team))->toBeTrue();
});
