<?php

use Illuminate\Support\Facades\Http;
use Modules\References\EuropePMC\Enums\Sources;
use Modules\References\EuropePMC\EuropePMC;
use Modules\References\Models\Record;

beforeEach(function () {
    putenv('EUROPE_PMC_ENDPOINT=https://europepmc.test');
    $_ENV['EUROPE_PMC_ENDPOINT'] = 'https://europepmc.test';
    $_SERVER['EUROPE_PMC_ENDPOINT'] = 'https://europepmc.test';
});

test('europe pmc search returns mapped records and total', function () {
    Http::fake([
        'https://europepmc.test/search*' => Http::response([
            'hitCount' => 1,
            'resultList' => [
                'result' => [
                    [
                        'id' => '12345',
                        'source' => 'MED',
                        'title' => 'Membrane transport study',
                        'authorString' => 'Doe J.',
                        'doi' => '10.1000/test',
                        'journalTitle' => 'Journal of Testing',
                        'pubYear' => '2025',
                        'isOpenAccess' => 'Y',
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = app(EuropePMC::class)->search('membrane transport');

    expect($result['total'])->toBe(1)
        ->and($result['records'])->toHaveCount(1)
        ->and($result['records'][0])->toBeInstanceOf(Record::class)
        ->and($result['records'][0]->source)->toBe(Sources::MED)
        ->and($result['records'][0]->title)->toBe('Membrane transport study')
        ->and($result['records'][0]->journal?->title)->toBe('Journal of Testing')
        ->and($result['records'][0]->isOpenAccess)->toBeTrue();
});

test('europe pmc detail returns null when id or source is missing', function () {
    $client = app(EuropePMC::class);

    expect($client->detail(null, Sources::MED))->toBeNull()
        ->and($client->detail('12345', null))->toBeNull();
});
