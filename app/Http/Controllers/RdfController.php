<?php

namespace App\Http\Controllers;

use App\Helpers\SPARQLurl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RdfController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * return redirect to external SPARQL endpoint for RDF/Semantic queries
     */
    public function simple_rdf($rdf_suffix)
    {
        // Validate the suffix after URL-decoding so we can return a custom
        // status code when the suffix contains disallowed characters.
        $decoded = urldecode($rdf_suffix);

        // Allowed characters: letters, numbers, slash, dash, underscore and dot
        $allowedPattern = '/^[A-Za-z0-9\/\-_.]+$/';
        if (!preg_match($allowedPattern, $decoded)) {
            return response()->json(['error' => 'Invalid RDF suffix'], 400);
        }

        $uiUrl = SPARQLurl::rdfDescribeUrl($rdf_suffix);
        return redirect()->away($uiUrl, 303);

    }
}
