<?php
namespace Modules\CdkDepict;

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

        $publicUrl = config('services.cdk_depict_url');
        if(!$publicUrl)
        {
            self::$STATUS = false;
            return;
        }

        try
        {
            self::$url_parameters['host'] = rtrim($publicUrl, '/');
            // Use internal URL for health check if configured, otherwise fall back to public URL.
            // The public URL's /test endpoint hits Laravel, not CDK Depict.
            $internalUrl = config('services.cdk_depict_internal_url');
            $pingHost = $internalUrl ? rtrim($internalUrl, '/') : self::$url_parameters['host'];
            self::try_connect($pingHost);
        }
        catch(Exception $e)
        {
           self::$STATUS = false;
           return;
        }
    }

    /**
     * Check remote server status
     */
    public static function try_connect(string $pingHost = '')
    {
        $host = $pingHost ?: self::$url_parameters['host'];
        $response = Http::get($host . '/test');
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

        return self::$url_parameters['host'] . '/depict/cot/svg?' . http_build_query($parameters); 
    }
}