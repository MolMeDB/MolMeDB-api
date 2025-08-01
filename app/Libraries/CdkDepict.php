<?php
namespace App\Libraries;

use Exception;
use Illuminate\Support\Facades\Http;

class CdkDepict 
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
            self::$url_parameters['host'] = rtrim(env('CDK_DEPICT_URL') ?? "", '/');
            // Try to connect   
            self::try_connect();
        }
        catch(Exception $e)
        {
           self::$STATUS = false;
           throw new Exception('Cannot establish connection to CdkDepict server.');
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

    public function get2dStructureUrl(string|null $smiles, float $scale = 2.2) : null | string
    {
        if(!$smiles)
            return null;

        $parameters = [
            'smi' => $smiles,
            'abbr' => 'reagents',
            'hdisp' => 'bridgehead',
            'showtitle' => 'true',
            'zoom' => $scale,
            'annotate' => 'none'
        ];

        return self::$url_parameters['host'] . '?' . http_build_query($parameters); 
    }
}