<?php
uses(Tests\TestCase::class);

test('test endpoint is accessible', function () {
    $response = $this->get('/api/epmc/test'); 
    $response->assertStatus(200);
});