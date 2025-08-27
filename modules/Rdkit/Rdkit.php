<?php

namespace Modules\Rdkit;

use App\Libraries\IdentifiersWorker;
use Exception;
use Illuminate\Support\Facades\Http;
use Modules\Rdkit\Response\ResponseInfo;

/**
 * Rdkit class for handling rdkit request
 * for server service
 */
class Rdkit extends IdentifiersWorker
{
    /** SERVICE STATUS */
    private static $STATUS = false;

    /** Holds connection 
     * @var array
    */
    protected static $url_parameters = array
    (
        'host' => ''
    );

    /** Holds info about last used identifier type */
    public $last_identifier = null;

    /** METRICS */
    const METRIC_TANIMOTO = "Tanimoto";
    const METRIC_DICE = "Dice";
    const METRIC_COSINE = "Cosine";
    const METRIC_SOKAL = "Sokal";
    const METRIC_RUSSEL = "Russel";
    const METRIC_KULCZYNSKI = "Kulczynski";
    const METRIC_MCCONNAUGHEY = "McConnaughey";

    const METRIC_DEFAULT = self::METRIC_COSINE;

    /**
     * Constructor
     * Checks service status
     */
    function __construct()
    {
        // Add to the system setting in next update
        if(!self::is_connected())
        {
            self::connect();
        }
    }

    /**
     * Connect to remote server
     */
    public static function connect()
    {
        if(self::is_connected())
        {
            return;
        }

        try
        {
            self::$url_parameters['host'] = rtrim(env('RDKIT_HOST') ?? "", '/');
            // Try to connect   
            self::try_connect();
        }
        catch(Exception $e)
        {
           self::$STATUS = false;
        }
    }

    /**
     * Check remote server status
     */
    public static function try_connect()
    {
        $response = Http::withUrlParameters(self::$url_parameters)->get('{+host}/test');
        self::$STATUS = $response->successful();
    }

    /**
     * Return service status
     * 
     * @return boolean
     */
    public static function is_connected()
    {
        return self::$STATUS;
    }

    /**
     * Checks, if given remote server is reachable
     * 
     * @return boolean
     */
    function is_reachable()
    {
        return $this->is_connected();
    }

    /**
     * Checks, if given identifier is valid
     * 
     * @param string $identifier
     * 
     * @return boolean
     */
    function is_valid_identifier($identifier)
    {
        return false;
    }

    /**
     * Returns PDB id for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_pdb($substance)
    {
        return false;
    }

    /**
     * Returns Pubchem id for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_pubchem($substance)
    {
        return false;
    }

    /**
     * Returns drugbank id for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_drugbank($substance)
    {
        return false;
    }

    /**
     * Returns chembl id for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_chembl($substance)
    {
        return false;
    }

    /**
     * Returns chebi id for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_chebi($substance)
    {
        return false;
    }

    /**
     * Returns SMILES for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_smiles($substance)
    {
        return false;
    }

    /**
     * Returns name for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_name($substance)
    {
        return false;
    }

    /**
     * Returns title for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_title($substance)
    {
        return false;
    }

    /**
     * Returns fingerprint of given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found.
     *  - Returned fingerprint must have lenght equal to 2048
     */
    function get_fingerprint(string $smiles)
    {
        if(!self::$STATUS)
        {
            return null;
        }

        $params = array
        (
            'smi' => $smiles
        );

        try
        {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withUrlParameters(self::$url_parameters)
                ->get('{+host}/structure/fingerprint', $params);

            if(!$response->successful())
            {
                return false;
            }

            $body = json_decode($response->body());
            if($body && $body?->data && $body->data?->fingerprint)
            {
                return $body->data->fingerprint;
            }

            return false;
        }
        catch(Exception $e)
        {
            return False;
        }
    }

    /**
     * For given SMILES returns it in canonized form
     * 
     * @param string $smiles
     * 
     * @return string|false - False if error occured
     */
    public function canonize_smiles($smiles)
    {
        if(!self::$STATUS)
        {
            return null;
        }

        $params = array
        (
            'smi' => $smiles
        );

        try
        {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withUrlParameters(self::$url_parameters)
                ->get('{+host}/structure/canonize', $params);

            if(!$response->successful())
            {
                return false;
            }

            $body = json_decode($response->body());
            if($body && $body?->data)
            {
                return $body->data;
            }

            return false;
        }
        catch(Exception $e)
        {
            return False;
        }
    }

    /**
     * For given SMILES returns it in canonized form
     * 
     * @param string $smiles
     * 
     * @return string|false - False if error occured
     */
    public function get_representant($smiles)
    {
        if(!self::$STATUS)
        {
            return null;
        }

        $params = array
        (
            'smi' => $smiles
        );

        try
        {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withUrlParameters(self::$url_parameters)
                ->get('{+host}/structure/representant', $params);

            if(!$response->successful())
            {
                return false;
            }

            $body = json_decode($response->body());
            if($body && $body?->data)
            {
                return $body->data;
            }

            return false;
        }
        catch(Exception $e)
        {
            return False;
        }
    }

    /**
     * Returns inchikey for given substance
     * 
     * @param Substances $substance
     * 
     * @return string|false - False, if not found
     */
    function get_inchikey($smiles)
    {
        if(!self::$STATUS)
        {
            return null;
        }

        $params = array
        (
            'smi' => $smiles
        );

        try
        {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withUrlParameters(self::$url_parameters)
                ->get('{+host}/structure/inchikey', $params);

            if(!$response->successful())
            {
                return false;
            }

            $body = json_decode($response->body());
            if($body && $body?->data)
            {
                return $body->data;
            }

            return false;
        }
        catch(Exception $e)
        {
            return False;
        }
    }

    /**
     * For given SMILES returns general info
     * 
     * @param string $smiles
     *    
     */
    public function get_general_info($smiles) : ResponseInfo | null | false
    {
        if(!self::$STATUS)
        {
            return null;
        }

        $params = array
        (
            'smi' => $smiles
        );

        try
        {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withUrlParameters(self::$url_parameters)
                ->get('{+host}/structure/info', $params);

            if(!$response->successful())
            {
                return false;
            }

            $body = json_decode($response->body());
            if($body && $body?->data)
            {
                return new ResponseInfo($body->data);
            }

            return false;
        }
        catch(Exception $e)
        {
            return False;
        }
    }

    /**
     * For given SMILES returns SDF content (3D structure)
     * 
     * @param string $smiles
     * 
     * @return $string
     */
    public function get_3d_structure($smiles)
    {
        if(!self::$STATUS)
        {
            return null;
        }

        $params = array
        (
            'smi' => $smiles
        );

        try
        {
            $response = Http::timeout(30)
                ->accept('application/octet-stream')
                ->withUrlParameters(self::$url_parameters)
                ->get('{+host}/structure/3d', $params);

            if(!$response->successful())
            {
                return false;
            }

            $body = $response->body();
            if($body)
            {
                return $body;
            }

            return false;
        }
        catch(Exception $e)
        {
            return False;
        }
    }
}