<?php
namespace App\Services\External\Chemical\Chebi;

use Exception;
use Illuminate\Support\Facades\Http;
use Predis\Connection\ConnectionException;

class Chebi
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = (string) config('services.chebi.base_api_url');
    }

    public function existsById(string $chebi_id): bool
    {
        $record = $this->getById($chebi_id);
        return $record !== null;
    }

    public function getById(string $chebi_id): ?ChebiRecord
    {
        $chebi_id = preg_replace('/^CHEBI:/i', '', $chebi_id);
        $response = $this->makeRequest("/compound/$chebi_id", [
                'only_ontology_parents' => false,
                'only_ontology_children' => false,
        ]);

        if(empty($response)) {
            return null;
        }

        return ChebiRecord::fromApiResponse($response);
    }

    public function getStructureById(string $chebi_id, $width = 300, $height = 300): ?string 
    {
        $url = rtrim($this->apiUrl, '/')."/compound/$chebi_id/structure/?width=$width&height=$height";

        try {
            $response = Http::connectTimeout(5)
                ->timeout(5)
                ->retry(2, 100)
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (Exception | ConnectionException $e) {
            return null;
        }
    }

    private function makeRequest(string $endpoint, array $params = [], int $timeout = 5): array|string|null
    {
        $url = rtrim($this->apiUrl, '/').'/'.ltrim($endpoint, '/');

        try {
            $response = Http::connectTimeout(5)
                ->timeout($timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->retry(2, 100)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (Exception | ConnectionException $e) {

            return null;
        }
    }
}