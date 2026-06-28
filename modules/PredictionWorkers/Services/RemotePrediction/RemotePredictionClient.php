<?php

namespace Modules\PredictionWorkers\Services\RemotePrediction;

use App\Models\Config;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionFile;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionHealth;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionJobSnapshot;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionJobSubmission;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionMembrane;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionMembraneCollection;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionStatistics;
use Modules\PredictionWorkers\DTO\RemotePrediction\RemotePredictionToken;
use Modules\PredictionWorkers\Enums\RemotePredictionArtifact;
use Modules\PredictionWorkers\Enums\RemotePredictionStep;
use Modules\PredictionWorkers\Exceptions\RemotePredictionDisabledException;
use Modules\PredictionWorkers\Exceptions\RemotePredictionException;
use Throwable;

class RemotePredictionClient
{
    public function isEnabled(): bool
    {
        return Config::boolean(
            Config::KEY_REMOTE_PREDICTION_ENABLED,
            (bool) config('prediction-workers.remote.enabled', false),
        );
    }

    public function baseUrl(): string
    {
        $url = rtrim(trim((string) Config::get(
            Config::KEY_REMOTE_PREDICTION_URL,
            config('prediction-workers.remote.base_url'),
        )), '/');

        if (
            filter_var($url, FILTER_VALIDATE_URL) === false
            || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https'
        ) {
            throw new RemotePredictionException('Remote prediction service URL must be a valid HTTPS URL.');
        }

        return $url;
    }

    public function health(): RemotePredictionHealth
    {
        return RemotePredictionHealth::fromArray(
            $this->jsonRequest('GET', '/health', public: true),
        );
    }

    public function openApi(): RemotePredictionStatistics
    {
        return RemotePredictionStatistics::fromArray(
            $this->jsonRequest('GET', '/openapi.json', public: true),
        );
    }

    public function createToken(string $name, int $expiresInDays = 30): RemotePredictionToken
    {
        return RemotePredictionToken::fromArray(
            $this->jsonRequest('POST', '/auth/tokens', [
                'name' => $name,
                'expires_in_days' => $expiresInDays,
            ], manager: true),
        );
    }

    public function revokeToken(int $tokenId): RemotePredictionToken
    {
        return RemotePredictionToken::fromArray(
            $this->jsonRequest('POST', "/auth/tokens/{$tokenId}/revoke", manager: true),
        );
    }

    public function accessStatistics(): RemotePredictionStatistics
    {
        return RemotePredictionStatistics::fromArray(
            $this->jsonRequest('GET', '/auth/stats', manager: true),
        );
    }

    public function registerMembrane(string $key, string $content): RemotePredictionMembrane
    {
        return RemotePredictionMembrane::fromArray(
            $this->jsonRequest('POST', '/membranes', [
                'key' => $key,
                'content' => $content,
            ]),
        );
    }

    public function uploadMembrane(string $key, string $content): RemotePredictionMembrane
    {
        $response = $this->request()
            ->withBody($content, 'text/plain')
            ->post('/membranes/'.rawurlencode($key));

        return RemotePredictionMembrane::fromArray($this->json($response));
    }

    public function membranes(): RemotePredictionMembraneCollection
    {
        return RemotePredictionMembraneCollection::fromArray(
            $this->jsonRequest('GET', '/membranes'),
        );
    }

    public function createJob(
        string $smiles,
        string $membraneKey,
        float $temperatureC,
        ?string $method = null,
    ): RemotePredictionJobSubmission {
        $payload = [
            'smiles' => $smiles,
            'membrane_key' => $membraneKey,
            'temperature_c' => $temperatureC,
        ];

        if ((bool) config('prediction-workers.remote.send_method_parameter', false) && $method !== null && $method !== '') {
            $payload['method'] = $method;
        }

        return RemotePredictionJobSubmission::fromArray(
            $this->jsonRequest('POST', '/jobs', $payload),
        );
    }

    public function jobStatus(
        string $smiles,
        int $eventsLimit = 30,
        ?string $membraneKey = null,
        ?float $temperatureC = null,
    ): RemotePredictionJobSnapshot {
        return RemotePredictionJobSnapshot::fromArray(
            $this->jsonRequest('POST', '/jobs/status', array_filter([
                'smiles' => $smiles,
                'events_limit' => $eventsLimit,
                'membrane_key' => $membraneKey,
                'temperature_c' => $temperatureC,
            ], fn (mixed $value): bool => $value !== null)),
        );
    }

    public function requeueJob(
        string $smiles,
        string $membraneKey,
        float $temperatureC,
    ): RemotePredictionJobSnapshot {
        return RemotePredictionJobSnapshot::fromArray(
            $this->jsonRequest('POST', '/jobs/requeue', [
                'smiles' => $smiles,
                'membrane_key' => $membraneKey,
                'temperature_c' => $temperatureC,
            ]),
        );
    }

    public function forceRequeueJob(
        string $smiles,
        string $membraneKey,
        float $temperatureC,
        RemotePredictionStep $step,
    ): RemotePredictionJobSnapshot {
        return RemotePredictionJobSnapshot::fromArray(
            $this->jsonRequest('POST', '/jobs/requeue/'.$step->value, [
                'smiles' => $smiles,
                'membrane_key' => $membraneKey,
                'temperature_c' => $temperatureC,
                'force' => true,
            ]),
        );
    }

    public function pipelineStatistics(): RemotePredictionStatistics
    {
        return RemotePredictionStatistics::fromArray(
            $this->jsonRequest('GET', '/stats'),
        );
    }

    public function downloadMolecule(string $smiles): RemotePredictionFile
    {
        return $this->fileRequest('POST', '/molecules/download', [
            'smiles' => $smiles,
        ]);
    }

    public function downloadCalculation(string $calculationId): RemotePredictionFile
    {
        return $this->fileRequest(
            'GET',
            '/calculations/'.rawurlencode($calculationId).'/download',
        );
    }

    public function downloadArtifact(
        string $calculationId,
        RemotePredictionArtifact $artifact,
    ): RemotePredictionFile {
        return $this->fileRequest(
            'GET',
            '/calculations/'.rawurlencode($calculationId).'/download/'.$artifact->value,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function jsonRequest(
        string $method,
        string $path,
        array $data = [],
        bool $manager = false,
        bool $public = false,
    ): array {
        $request = $public
            ? $this->publicRequest()
            : ($manager ? $this->managerRequest() : $this->request());

        $response = $request->send($method, $path, $this->requestOptions($method, $data));

        return $this->json($response);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fileRequest(string $method, string $path, array $data = []): RemotePredictionFile
    {
        $response = $this->request(download: true)
            ->send($method, $path, $this->requestOptions($method, $data));

        $this->throwForResponse($response);

        return new RemotePredictionFile(
            contents: $response->body(),
            filename: $this->filename($response, basename($path)),
            mimeType: (string) ($response->header('Content-Type') ?? 'application/octet-stream'),
        );
    }

    public function ensureValidToken(): void
    {
        if ($this->hasValidToken()) {
            return;
        }

        Cache::lock('remote-prediction:token-refresh', 60)->block(15, function (): void {
            if (! $this->hasValidToken()) {
                $this->refreshToken();
            }
        });
    }

    private function hasValidToken(): bool
    {
        $token = Config::get(Config::KEY_REMOTE_PREDICTION_TOKEN);
        $expiresAt = Config::get(Config::KEY_REMOTE_PREDICTION_TOKEN_EXPIRES_AT);

        return $token !== null && $token !== ''
            && $expiresAt !== null
            && now()->lt(CarbonImmutable::parse($expiresAt)->subDay());
    }

    private function refreshToken(): void
    {
        $oldTokenId = Config::get(Config::KEY_REMOTE_PREDICTION_TOKEN_ID);

        $newToken = $this->createToken('molmedb-worker', 30);

        DB::transaction(function () use ($newToken): void {
            Config::set(Config::KEY_REMOTE_PREDICTION_TOKEN, $newToken->token);
            Config::set(Config::KEY_REMOTE_PREDICTION_TOKEN_ID, (string) $newToken->id);
            Config::set(Config::KEY_REMOTE_PREDICTION_TOKEN_EXPIRES_AT, $newToken->expiresAt?->toIso8601String());
        });

        if ($oldTokenId !== null && $oldTokenId !== '' && (int) $oldTokenId !== $newToken->id) {
            try {
                $this->revokeToken((int) $oldTokenId);
            } catch (Throwable) {
                // Old token may already be expired or revoked
            }
        }
    }

    private function request(bool $download = false): PendingRequest
    {
        $token = trim((string) Config::get(Config::KEY_REMOTE_PREDICTION_TOKEN, ''));

        if ($token === '') {
            throw new RemotePredictionException('Remote prediction service Bearer token is not configured.');
        }

        return $this->baseRequest($download)
            ->withToken($token);
    }

    private function managerRequest(): PendingRequest
    {
        $secret = trim((string) config('prediction-workers.remote.manager_secret', ''));

        if ($secret === '') {
            throw new RemotePredictionException('Remote prediction service manager secret is not configured.');
        }

        return $this->baseRequest()
            ->withHeader('X-API-Manager-Secret', $secret);
    }

    private function publicRequest(): PendingRequest
    {
        return $this->baseRequest();
    }

    private function baseRequest(bool $download = false): PendingRequest
    {
        $this->ensureEnabled();

        $baseUrl = $this->baseUrl();

        if ($baseUrl === '') {
            throw new RemotePredictionException('Remote prediction service URL is not configured.');
        }

        $timeout = $download
            ? (int) config('prediction-workers.remote.download_timeout', 300)
            : (int) config('prediction-workers.remote.timeout', 60);

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->connectTimeout((int) config('prediction-workers.remote.connect_timeout', 5))
            ->timeout($timeout)
            ->retry(
                $this->retryDelays(),
                when: static fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()),
                throw: false,
            );
    }

    private function ensureEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new RemotePredictionDisabledException;
        }
    }

    /**
     * @return array<int, int>
     */
    private function retryDelays(): array
    {
        return array_values(array_map(
            'intval',
            (array) config('prediction-workers.remote.retry_delays', [200, 500, 1000]),
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function requestOptions(string $method, array $data): array
    {
        if ($data === []) {
            return [];
        }

        return strtoupper($method) === 'GET'
            ? ['query' => $data]
            : ['json' => $data];
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        $this->throwForResponse($response);
        $data = $response->json();

        if (! is_array($data)) {
            throw new RemotePredictionException(
                'Remote prediction service returned an invalid JSON response.',
                $response->status(),
            );
        }

        return $data;
    }

    private function throwForResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $payload = $response->json();
        $detail = is_array($payload) ? ($payload['detail'] ?? $payload) : $response->body();
        $errorCode = is_array($detail) && isset($detail['code'])
            ? (string) $detail['code']
            : null;
        $message = match (true) {
            is_string($detail) && $detail !== '' => $detail,
            is_array($detail) && isset($detail['message']) => (string) $detail['message'],
            default => "Remote prediction service request failed with HTTP {$response->status()}.",
        };

        throw new RemotePredictionException(
            message: $message,
            statusCode: $response->status(),
            detail: is_array($detail) || is_string($detail) ? $detail : null,
            errorCode: $errorCode,
        );
    }

    private function filename(Response $response, string $fallback): string
    {
        $disposition = (string) $response->header('Content-Disposition');

        if (preg_match('/filename\\*=UTF-8\'\'([^;]+)/i', $disposition, $matches) === 1) {
            return rawurldecode(trim($matches[1], '"'));
        }

        if (preg_match('/filename="?([^";]+)"?/i', $disposition, $matches) === 1) {
            return trim($matches[1]);
        }

        return $fallback;
    }
}
