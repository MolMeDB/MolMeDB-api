<?php

require_once __DIR__.'/api_test_helpers.php';

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('predictions datasets endpoint requires authentication', function () {
    $this->getJson(apiRoutePath('api/predictions/datasets'))
        ->assertUnauthorized();
});

test('predictions dataset detail endpoint requires authentication', function () {
    $this->getJson(apiRoutePath('api/predictions/datasets/1'))
        ->assertUnauthorized();
});

test('predictions dataset records endpoint requires authentication', function () {
    $this->getJson(apiRoutePath('api/predictions/datasets/1/records'))
        ->assertUnauthorized();
});

test('predictions dataset structures endpoint requires authentication', function () {
    $this->getJson(apiRoutePath('api/predictions/datasets/1/structures'))
        ->assertUnauthorized();
});

test('predictions by structure endpoint requires authentication', function () {
    $this->getJson(apiRoutePath('api/predictions/byStructure/1'))
        ->assertUnauthorized();
});
