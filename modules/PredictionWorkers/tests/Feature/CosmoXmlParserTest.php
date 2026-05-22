<?php

use Illuminate\Support\Facades\Storage;
use Modules\PredictionWorkers\DTO\JobResult;
use Modules\PredictionWorkers\DTO\PredictionResultJson;
use Modules\PredictionWorkers\DTO\SoluteResult;
use Modules\PredictionWorkers\Models\PredictionFile;
use Modules\PredictionWorkers\Services\CosmoXmlParser;

test('cosmo xml parser parses jobs and solutes from valid xml file', function () {
    Storage::fake('predictions-test');

    Storage::disk('predictions-test')->put('results/cosmo.xml', <<<'XML'
<micoutput>
    <joblist>
        <job>
            <jobnumber>1</jobnumber>
            <symmetry>planar</symmetry>
            <nrlayer>3</nrlayer>
            <layerposition>0.0 1.234 2.345</layerposition>
            <temperature>310.15</temperature>
            <solutelist>
                <solute>
                    <meanposition>1.5</meanposition>
                    <logP_micelle_water>2.75</logP_micelle_water>
                    <logPerm_membrane_cm_s>-4.5</logPerm_membrane_cm_s>
                    <distribution>0.5 0.25 0.125</distribution>
                </solute>
            </solutelist>
        </job>
    </joblist>
</micoutput>
XML);

    $file = new PredictionFile([
        'storage' => 'predictions-test',
        'path' => 'results/cosmo.xml',
    ]);

    $result = app(CosmoXmlParser::class)->parse($file);

    expect($result)->toBeInstanceOf(PredictionResultJson::class)
        ->and($result->jobs)->toHaveCount(1)
        ->and($result->jobs[0]->jobNumber)->toBe('1')
        ->and($result->jobs[0]->symmetry)->toBe('planar')
        ->and($result->jobs[0]->layerCount)->toBe(3)
        ->and($result->jobs[0]->layerPositions)->toBe([0.0, 1.2, 2.3])
        ->and($result->jobs[0]->temperature)->toBe(37.0)
        ->and($result->jobs[0]->solutes)->toHaveCount(1)
        ->and($result->jobs[0]->solutes[0]->meanPosition)->toBe(1.5)
        ->and($result->jobs[0]->solutes[0]->logK)->toBe(2.75)
        ->and($result->jobs[0]->solutes[0]->logPerm)->toBe(-4.5)
        ->and($result->jobs[0]->solutes[0]->energyValues)->toBe([-0.85, -0.43, 0.0]);
});

test('cosmo xml parser throws when xml file is missing', function () {
    Storage::fake('predictions-test');

    $file = new PredictionFile([
        'storage' => 'predictions-test',
        'path' => 'missing.xml',
    ]);

    expect(fn () => app(CosmoXmlParser::class)->parse($file))
        ->toThrow(RuntimeException::class, 'COSMO XML file not found');
});

test('prediction result dto serializes nested jobs and solutes', function () {
    $result = new PredictionResultJson([
        new JobResult(
            jobNumber: '1',
            symmetry: 'planar',
            layerCount: 2,
            layerPositions: [0.0, 1.0],
            temperature: 25.0,
            solutes: [
                new SoluteResult(
                    meanPosition: 0.5,
                    logK: 1.2,
                    logPerm: -3.4,
                    energyValues: [0.3, 0.0],
                ),
            ],
        ),
    ]);

    expect($result->jsonSerialize())->toBe([
        [
            'symmetry' => 'planar',
            'layer_count' => 2,
            'layer_positions' => [0.0, 1.0],
            'temperature' => 25.0,
            'solutes' => [
                [
                    'mean_position' => 0.5,
                    'logK' => 1.2,
                    'logPerm' => -3.4,
                    'energy_values' => [0.3, 0.0],
                ],
            ],
        ],
    ]);
});
