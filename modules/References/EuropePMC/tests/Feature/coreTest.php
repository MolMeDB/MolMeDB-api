<?php

// Pest's uses() needs a fully-qualified class name here, not a `use` import
// — an imported reference doesn't resolve correctly in how Pest evaluates
// test files.
uses(Tests\TestCase::class);

use Illuminate\Support\Facades\Http;
use Modules\References\EuropePMC\Enums\Sources;
use Modules\References\EuropePMC\EuropePMC;
use Modules\References\Models\Record;

$testID = '37842337';

beforeEach(function () {
    // EuropePMC's constructor reads config('services.europe_pmc.endpoint')
    // — point it at a fake host so these tests never depend on real network
    // access to the actual EuropePMC API.
    config()->set('services.europe_pmc.endpoint', 'https://europepmc.test');
});

test('EuropePMC API returns valid response', function () {
    Http::fake([
        'https://europepmc.test/search*' => Http::response([
            'hitCount' => 1,
            'resultList' => [
                'result' => [[
                    'id' => '1',
                    'source' => 'MED',
                    'title' => 'Test paper',
                ]],
            ],
        ], 200),
    ]);

    $service = new EuropePMC;

    $response = Http::timeout(10)
        ->acceptJson()
        ->get("{$service->url()}/search", [
            'query' => 'molmedb',
            'resultType' => 'core',
        ]);

    $result = $service->processResponse($response);

    expect($result)
        ->toBeArray()
        ->and($result)->toHaveKeys(['hitCount', 'resultList'])
        ->and($result['hitCount'])->toBeInt()
        ->and($result['resultList'])->toBeArray()
        ->and($result['resultList'])->toHaveKey('result')
        ->and($result['resultList']['result'])->toBeArray();
});

test('can search by query', function () {
    Http::fake([
        'https://europepmc.test/search*' => Http::response([
            'hitCount' => 1,
            'resultList' => [
                'result' => [[
                    'id' => '1',
                    'source' => 'MED',
                    'title' => 'Test paper',
                ]],
            ],
        ], 200),
    ]);

    $query = 'molmedb';
    $service = new EuropePMC;
    $result = $service->search($query);

    expect($result)
        ->toBeArray();
});

test('can get citation list', function () use ($testID) {
    Http::fake([
        "https://europepmc.test/MED/{$testID}/citations*" => Http::response([
            'hitCount' => 1,
            'citationList' => [
                'citation' => [[
                    'id' => '2',
                    'source' => 'MED',
                    'title' => 'Citing paper',
                ]],
            ],
        ], 200),
    ]);

    $service = new EuropePMC;
    $result = $service->citationList($testID, Sources::MED, 1, 1);

    expect($result)
        ->toBeArray()
        ->and($result)->toHaveKeys(['total', 'records'])
        ->and($result['total'])->toBeInt()
        ->and($result['records'])->toBeArray();
});

test('can get references list', function () use ($testID) {
    Http::fake([
        "https://europepmc.test/MED/{$testID}/references*" => Http::response([
            'hitCount' => 1,
            'referenceList' => [
                'reference' => [[
                    'id' => '3',
                    'source' => 'MED',
                    'title' => 'Referenced paper',
                ]],
            ],
        ], 200),
    ]);

    $service = new EuropePMC;
    $result = $service->referencesList($testID, Sources::MED, 1, 1);

    expect($result)
        ->toBeArray()
        ->and($result)->toHaveKeys(['total', 'records'])
        ->and($result['total'])->toBeInt()
        ->and($result['records'])->toBeArray();
});

test('can get detail', function () use ($testID) {
    Http::fake([
        "https://europepmc.test/article/MED/{$testID}*" => Http::response([
            'result' => [
                'id' => $testID,
                'source' => 'MED',
                'title' => 'Detail paper',
            ],
        ], 200),
    ]);

    $service = new EuropePMC;
    $result = $service->detail($testID, Sources::MED);

    expect($result)
        ->toBeObject()
        ->and(get_class($result))->toBe(Record::class);
});
