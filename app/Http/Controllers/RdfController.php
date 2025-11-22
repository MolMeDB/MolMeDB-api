<?php

namespace App\Http\Controllers;

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
        $request = request();

        // Decode suffix and validate allowed characters. The suffix may contain
        // letters, numbers and slashes (per your specification). Reject anything
        // suspicious to avoid injection into the SPARQL query.
        $suffix = urldecode($rdf_suffix);

        if (!preg_match('/^[A-Za-z0-9\/\-_.]+$/', $suffix)) {
            return response()->json(['error' => 'Invalid RDF suffix'], 400);
        }

        // Build the full URI — ensure there is a single slash between prefix
        // and suffix and validate the resulting URL.
        $fullUri = 'https://rdf.molmedb.upol.cz/' . ltrim($suffix, '/');

        // If the client prefers HTML (likely a browser), redirect to the
        // SPARQL UI where the fragment contains the query (browsers keep
        // fragments client-side). For machine clients requesting RDF/Turtle/etc.,
        // we'll proxy the request and forward the Accept header.
        if ($request->accepts('text/html')) {
            $uiUrl = 'https://idsm.elixir-czech.cz/sparql/endpoint/molmedb#query=DESCRIBE%20%3C' . $fullUri . '%3E';
            return redirect()->away($uiUrl);
        }



        if (!filter_var($fullUri, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Constructed URI is invalid'], 400);
        }

        // Incoming Accept header (default to */* if absent)
        $accept = $request->header('Accept', '*/*');

        // External SPARQL endpoint and the DESCRIBE query for the constructed URI
        $endpoint = 'https://idsm.elixir-czech.cz/sparql/endpoint/molmedb';
        $sparql = 'DESCRIBE <' . $fullUri . '>';

        try {
            $resp = Http::withHeaders(['Accept' => $accept])
                ->timeout(10)
                ->get($endpoint, ['query' => $sparql]);

            $status = $resp->status();
            $body = $resp->body();
            $contentType = $resp->header('Content-Type', $accept);

            return response($body, $status)
                ->header('Content-Type', $contentType);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Upstream request failed',
                'message' => $e->getMessage(),
            ], 502);
        }
    }
}
