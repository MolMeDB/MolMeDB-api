<?php

use Modules\PredictionWorkers\Models\Prediction;

test('prediction progress clamps step values', function (?int $step, int $expectedStep, int $expectedPercent) {
    expect(Prediction::progressStepValue($step))->toBe($expectedStep)
        ->and(Prediction::progressPercent($step))->toBe($expectedPercent);
})->with([
    'null step' => [null, 0, 0],
    'negative step' => [-1, 0, 0],
    'pending step' => [Prediction::STEP_PENDING, 0, 0],
    'middle step' => [Prediction::STEP_COSMO, 4, 57],
    'final step' => [Prediction::STEP_RESULT_DB_STORE, 7, 100],
    'over final step' => [99, 7, 100],
]);

test('prediction result marks progress as complete', function () {
    expect(Prediction::progressStepValue(Prediction::STEP_COSMO, Prediction::STATE_RUNNING, 123))->toBe(Prediction::finalStep())
        ->and(Prediction::progressPercent(Prediction::STEP_COSMO, Prediction::STATE_RUNNING, 123))->toBe(100);
});
