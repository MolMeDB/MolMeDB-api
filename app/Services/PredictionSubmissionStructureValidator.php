<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\PredictionWorkers\Models\Prediction;

class PredictionSubmissionStructureValidator
{
    /** @var array<int|string, array<int, string>|null> */
    private array $validationErrorsByStructure = [];

    public function __construct(
        private readonly PredictionSmilesCanonicalizer $smilesCanonicalizer,
    ) {}

    public function passes(Prediction $prediction): bool
    {
        $smiles = trim((string) $prediction->predictionStructure?->canonical_smiles);
        $structureKey = $prediction->structure_id ?? 'prediction-'.$prediction->getKey();

        if (! array_key_exists($structureKey, $this->validationErrorsByStructure)) {
            try {
                $this->smilesCanonicalizer->canonicalize([$smiles]);
                $this->validationErrorsByStructure[$structureKey] = null;
            } catch (ValidationException $exception) {
                $this->validationErrorsByStructure[$structureKey] = collect($exception->errors())
                    ->flatten()
                    ->map(fn (mixed $error): string => (string) $error)
                    ->values()
                    ->all();
            }
        }

        $errors = $this->validationErrorsByStructure[$structureKey];

        if ($errors === null) {
            return true;
        }

        $message = 'Prediction structure validation failed: '.implode(' ', $errors);
        $logs = is_array($prediction->logs) ? $prediction->logs : [];
        $logs[] = [
            'type' => 'STRUCTURE VALIDATION',
            'context' => 'error',
            'message' => $message,
            'payload' => [
                'structure_id' => $prediction->structure_id,
                'smiles' => $smiles,
                'errors' => $errors,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $prediction->forceFill([
            'state' => Prediction::STATE_ERROR,
            'step' => Prediction::STEP_PENDING,
            'remote_last_status_at' => now(),
            'remote_error_message' => $message,
            'logs' => $logs,
        ])->save();

        Log::error($message, [
            'prediction_id' => $prediction->getKey(),
            'structure_id' => $prediction->structure_id,
            'smiles' => $smiles,
            'errors' => $errors,
        ]);

        return false;
    }
}
