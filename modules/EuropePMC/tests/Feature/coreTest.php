<?php

use Illuminate\Support\Facades\Http;
use Modules\EuropePMC\Enums\Sources;
use Modules\EuropePMC\EuropePMC;

$testID = '37842337';

test('EuropePMC API returns valid response', function () {
    $service = new EuropePMC();
    
    $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$service->url()}/search", [
                    'query' => 'molmedb',
                    'resultType' => 'core'
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



test('can search by query', function() {
    $query = "molmedb";
    $service = new EuropePMC();
    $result = $service->search($query);

    expect($result)
        ->toBeArray();
});


test('can get citation list', function() use ($testID) {
    $service = new EuropePMC();
    $result = $service->citationList($testID, Sources::MED, 1, 1);

    expect($result)
        ->toBeArray()
            ->and($result)->toHaveKeys(['total', 'records'])
            ->and($result['total'])->toBeInt()
            ->and($result['records'])->toBeArray();
});

test('can get references list', function() use ($testID) {
    $service = new EuropePMC();
    $result = $service->referencesList($testID, Sources::MED, 1, 1);
    
    expect($result)
        ->toBeArray()
            ->and($result)->toHaveKeys(['total', 'records'])
            ->and($result['total'])->toBeInt()
            ->and($result['records'])->toBeArray();
});


test('can get detail', function() use ($testID) {
    $service = new EuropePMC();
    $result = $service->detail($testID, Sources::MED);

    expect($result)
        ->toBeObject()
        ->and(get_class($result))->toBe(Modules\EuropePMC\Models\Record::class);
});