<?php
namespace App\Services\External\Chemical\Chebi;

class ChebiNames 
{
    public function __construct(
        public ?string $iupac_name = null,
        public ?array $synonyms = null
    ){}

    public static function fromApiResponse(array $response) : self 
    {
        $iupac_name = !empty($response['names']['IUPAC NAME']) ? $response['names']['IUPAC NAME'][0]['ascii_name'] : null;
        $iupac_name = $iupac_name ? ucfirst(strtolower($iupac_name)) : null;

        $synonyms = [];
        
        foreach($response['names']['SYNONYM'] ?? [] as $synonym)
        {
            $synonyms[] = $synonym['ascii_name'] ? ucfirst(strtolower($synonym['ascii_name'])) : null;
        }

        return new self($iupac_name, array_unique($synonyms));
    }
}