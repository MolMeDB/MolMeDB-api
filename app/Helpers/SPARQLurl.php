<?php
namespace App\Helpers;

class SPARQLurl {
    public static function rdfDescribeUrl($rdf_suffix)
    {
        $ep = env('SPARQL_ENDPOINT');
        $rdf_prefix = env('DB_RDF_PREFIX');
        $sparql_prefix = env('SPARQL_QUERY_PREFIX');
        $suffix = urldecode($rdf_suffix);

        $fullUri = $rdf_prefix . ltrim($suffix, '/');
        $query = rawurlencode('DESCRIBE <'.$fullUri.'>');
        $uiUrl = $ep.$sparql_prefix.$query;
        return $uiUrl;
    }
}
