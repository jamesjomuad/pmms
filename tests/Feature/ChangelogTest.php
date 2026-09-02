<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from changelog to the login page', function () {
    $response = $this->get(route('changelog.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view the changelog', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('changelog.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Changelog')
        ->has('releases')
        ->has('stats')
        ->has('stats.total_releases')
        ->has('stats.total_changes')
        ->has('stats.latest_version')
    );
});
