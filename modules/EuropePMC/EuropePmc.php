<?php
namespace Modules\EuropePMC;

use Dotenv\Dotenv;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\EuropePMC\Enums\Query\SortBy;
use Modules\EuropePMC\Enums\Query\SortOrder;
use Modules\EuropePMC\Enums\Sources;
use Modules\EuropePMC\Models\Record;

class EuropePMC
{
    private $path = 'modules/EuropePMC/';
    
    protected string $baseUrl = '';

    public function __construct()
    {
        if (file_exists($this->path . './.env')) {
            $dotenv = Dotenv::createImmutable($this->path);
            $dotenv->load();
        }

        $this->baseUrl = env('EUROPE_PMC_ENDPOINT');
    }


    public function url() {
        return $this->baseUrl;
    }

    /**
     * Returns list of EuropePMC articles matching the query
     * 
     * @return Record[]
     */
    public function search(
        string $query, 
        SortBy $sortBy = SortBy::SCORE,
        SortOrder $sortOrder = SortOrder::DESC,
        $page = 1,
        $pageSize = 25
    )
    {
        try
        {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$this->baseUrl}/search", [
                    'query' => $query,
                    'resultType' => 'core',
                    'sort' => "$sortBy->value $sortOrder->value",
                    'format' => 'json',
                    'page' => $page,
                    'pageSize' => $pageSize
                ]);

            $response = $this->processResponse($response);

            if(!$response) return null;

            $validator = Validator::make($response, [
                'resultList.result' => 'required|array',
                'hitCount' => 'required|integer'
            ]);

            if($validator->fails()){
                return null;
            }

            return [
                'total' => $response['hitCount'],
                'records' => array_map(fn($record) => Record::from($record), $response['resultList']['result'])
            ];
        }
        catch(\Exception $e)
        {
            Log::error($e->getMessage(), ['query' => $query]);
            return null;
        }
    }

    /**
     * Returns detail of article
     */
    public function detail(
        string $id,
        Sources $source
    )
    {
        try
        {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$this->baseUrl}/article/$source->value/$id", [
                    'resultType' => 'core',
                    'format' => 'json'
                ]);

            $response = $this->processResponse($response);

            if(!$response) return null;

            $validator = Validator::make($response, [
                'result' => 'required|array'
            ]);

            if($validator->fails()){
                return null;
            }

            return Record::from($response['result']);

        }
        catch(Exception $e)
        {
            Log::error($e->getMessage(), ['id' => $id, 'source', $source]);
            return null;
        }
    }

    /**
     * Returns list of articles citing the given article 
     */
    public function citationList(
        string $id, 
        Sources $source, 
        $page = 1, 
        $pageSize = 25)
    {
        try
        {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$this->baseUrl}/$source->value/$id/citations", [
                    'resultType' => 'core',
                    'format' => 'json',
                    'page' => $page,
                    'pageSize' => $pageSize
                ]);

            $response = $this->processResponse($response);

            if(!$response) return null;

            $validator = Validator::make($response, [
                'citationList.citation' => 'required|array',
                'hitCount' => 'required|integer'
            ]);

            if($validator->fails()){
                return null;
            }

            return [
                'total' => $response['hitCount'],
                'records' => array_map(fn($record) => Record::from($record), $response['citationList']['citation'])
            ];
        }
        catch(Exception $e)
        {
            Log::error($e->getMessage(), ['id' => $id, 'source', $source]);
            return null;
        }
    }

    /**
     * Returns list of references
     */
    public function referencesList(
        string $id, 
        Sources $source, 
        $page = 1, 
        $pageSize = 25)
    {
        try
        {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$this->baseUrl}/$source->value/$id/references", [
                    'resultType' => 'core',
                    'format' => 'json',
                    'page' => $page,
                    'pageSize' => $pageSize
                ]);

            $response = $this->processResponse($response);

            if(!$response) return null;

            $validator = Validator::make($response, [
                'referenceList.reference' => 'required|array',
                'hitCount' => 'required|integer'
            ]);

            if($validator->fails()){
                return null;
            }

            return [
                'total' => $response['hitCount'],
                'records' => array_map(fn($record) => Record::from($record), $response['referenceList']['reference'])
            ];
        }
        catch(Exception $e)
        {
            Log::error($e->getMessage(), ['id' => $id, 'source', $source]);
            return null;
        }
    }


    public function processResponse(\Illuminate\Http\Client\Response $response)
    {
        if($response->successful())
        {
            return $response->json();
        }
        elseif ($response->clientError())
        {
            Log::error('Client error. Code:' . $response->status());
            return null;
        }
        elseif ($response->serverError())
        {
            Log::error('Server error. Code:' . $response->status());
            return null;
        }
        else 
        {
            Log::error('Unknown error. Code:' . $response->status());
            return null;
        }
    }
}



