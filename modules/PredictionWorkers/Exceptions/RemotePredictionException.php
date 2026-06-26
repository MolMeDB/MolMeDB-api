<?php

namespace Modules\PredictionWorkers\Exceptions;

use RuntimeException;
use Throwable;

class RemotePredictionException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|string|null  $detail
     */
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly array|string|null $detail = null,
        public readonly ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }
}
