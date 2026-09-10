<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicApiCors
{
    /**
     * Open CORS for the unauthenticated public API (GET-only, no credentials),
     * independent of config/cors.php which stays scoped to the credentialed
     * frontend origin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept');

        // The framework's global HandleCors middleware (config/cors.php) still runs
        // before this and may add credentials support for the authenticated
        // frontend origin — invalid/misleading combined with an open '*' origin here.
        $response->headers->remove('Access-Control-Allow-Credentials');

        return $response;
    }
}
