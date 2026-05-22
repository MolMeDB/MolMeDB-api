<?php

require_once __DIR__.'/api_test_helpers.php';

use App\Models\User;

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('api test endpoint returns ok response', function () {
    $this->getJson(apiRoutePath('api/test'))
        ->assertOk()
        ->assertJson([
            'message' => 'OK',
        ]);
});

test('api epmc test endpoint returns ok response', function () {
    $this->getJson(apiRoutePath('api/epmc/test'))
        ->assertOk()
        ->assertJson([
            'message' => 'OK',
        ]);
});

test('api user endpoint requires authentication', function () {
    $this->getJson(apiRoutePath('api/user'))
        ->assertUnauthorized();
});

test('api user endpoint returns authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->getJson(apiRoutePath('api/user'))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});
