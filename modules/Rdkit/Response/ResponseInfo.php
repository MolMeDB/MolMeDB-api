<?php
namespace Modules\Rdkit\Response;

class ResponseInfo
{
    public $raw;
    public $canonized_smiles;
    public $inchi;
    public $inchikey;
    public $mw;
    public $logp;

    public function __construct(object $data) 
    {
        $this->raw = $data;
        $this->canonized_smiles = $data->canonized_smiles;
        $this->inchi = $data->inchi;
        $this->inchikey = $data->inchikey;
        $this->mw = $data->MW;
        $this->logp = $data->LogP;
    }
}