<?php
uses(Tests\TestCase::class);

use Illuminate\Support\Facades\Http;
use Modules\References\CrossRef\CrossRef;

$doi = '10.1093/database/baz078';

test('CrossRef API returns valid response', function () use ($doi) {
    $service = new CrossRef();
    
    $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$service->url()}/works/$doi");

    $result = $service->processResponse($response);

    expect($result)
        ->toBeArray()
        ->and($result)->toHaveKeys(['status', 'message'])
        ->and($result['status'])->toBe("ok")
        ->and($result['message'])->toHaveKey('DOI');
});


test('can obtain detail by DOI', function() use ($doi) {
    $service = new CrossRef();
    $result = $service->work($doi);

    expect($result)
        ->toBeObject()
        ->and(get_class($result))->toBe(Modules\References\Models\Record::class);
});