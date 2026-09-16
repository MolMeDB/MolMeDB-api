<?php

namespace Modules\PredictionWorkers\DTO;

use JsonSerializable;

class PredictionResultJson implements JsonSerializable
{
    /** @var JobResult[] */
    public array $jobs;

    public function __construct(array $jobs)
    {
        $this->jobs = $jobs;
    }

    public function jsonSerialize(): array
    {
        return array_map(fn ($job) => $job->jsonSerialize(), $this->jobs);
    }
}