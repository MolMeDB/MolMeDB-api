<?php

namespace Modules\PredictionWorkers\DTO\RemotePrediction;

final class RemotePredictionFile
{
    public function __construct(
        public readonly string $contents,
        public readonly string $filename,
        public readonly string $mimeType,
    ) {}

    public function size(): int
    {
        return strlen($this->contents);
    }

    public function saveTo(string $path): bool
    {
        return file_put_contents($path, $this->contents) !== false;
    }
}
