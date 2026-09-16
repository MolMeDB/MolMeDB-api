<?php

namespace Modules\PredictionWorkers\Services;

use Illuminate\Support\Facades\Storage;
use Modules\PredictionWorkers\DTO\JobResult;
use Modules\PredictionWorkers\DTO\PredictionResultJson;
use Modules\PredictionWorkers\DTO\SoluteResult;
use Modules\PredictionWorkers\Models\PredictionFile;
use RuntimeException;

class CosmoXmlParser
{
    public function parse(PredictionFile $file): PredictionResultJson
    {
        $disk = Storage::disk($file->storage);

        if (! $disk->exists($file->path)) {
            throw new RuntimeException(
                "COSMO XML file not found on disk [{$file->storage}] path [{$file->path}]"
            );
        }

        $content = $disk->get($file->path);

        if (! $content) {
            throw new RuntimeException('Unable to read COSMO XML file.');
        }

        return $this->parseString($content);
    }

    public function parseString(string $content): PredictionResultJson
    {
        $xml = simplexml_load_string($content);

        if (! $xml || $xml->getName() !== 'micoutput') {
            throw new RuntimeException('Invalid root element. Expected <micoutput>.');
        }

        if (! isset($xml->joblist)) {
            throw new RuntimeException('Missing <joblist> element.');
        }

        $jobs = [];

        foreach ($xml->joblist->job as $jobNode) {

            $temperatureK = (float) trim((string) $jobNode->temperature);
            $temperatureC = $temperatureK > 200
                ? $temperatureK - 273.15
                : $temperatureK;

            $layerPositions = $this->parseFloatList(
                (string) $jobNode->layerposition,
                roundTo: 1
            );

            $solutes = [];

            if (isset($jobNode->solutelist->solute)) {

                foreach ($jobNode->solutelist->solute as $soluteNode) {

                    $energyValues = $this->parseEnergyDistribution(
                        (string) $soluteNode->distribution,
                        $temperatureK
                    );

                    $solutes[] = new SoluteResult(
                        meanPosition: (float) $soluteNode->meanposition,
                        logK: (float) $soluteNode->logP_micelle_water,
                        logPerm: isset($soluteNode->logPerm_membrane_cm_s)
                            ? (float) $soluteNode->logPerm_membrane_cm_s
                            : null,
                        energyValues: $energyValues
                    );
                }
            }

            $jobs[] = new JobResult(
                jobNumber: trim((string) $jobNode->jobnumber),
                symmetry: trim((string) $jobNode->symmetry),
                layerCount: (int) $jobNode->nrlayer,
                layerPositions: $layerPositions,
                temperature: $temperatureC,
                solutes: $solutes
            );
        }

        return new PredictionResultJson($jobs);
    }

    private function parseFloatList(string $input, ?int $roundTo = null): array
    {
        $values = preg_split('/\s+/', trim($input));

        return array_map(function ($v) use ($roundTo) {
            $float = (float) $v;

            return $roundTo !== null ? round($float, $roundTo) : $float;
        }, array_filter($values));
    }

    private function parseEnergyDistribution(string $distribution, float $temperatureK): array
    {
        $values = preg_split('/\s+/', trim($distribution));

        $energies = [];

        foreach ($values as $v) {

            if (! is_numeric($v) || (float) $v == 0.0) {
                return [];
            }

            $probability = (float) $v;

            // ΔG = -RT ln(p) / 4185
            $deltaG = -8.314 * $temperatureK * log($probability) / 4185;

            $energies[] = $deltaG;
        }

        if (! empty($energies)) {
            $offset = end($energies);
            $energies = array_map(
                fn ($e) => round($e - $offset, 2),
                $energies
            );
        }

        return $energies;
    }
}
