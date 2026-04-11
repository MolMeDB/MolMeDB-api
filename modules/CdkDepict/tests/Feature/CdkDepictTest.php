<?php

use Illuminate\Support\Facades\Http;
use Modules\CdkDepict\CdkDepict;

beforeEach(function () {
    resetCdkDepictState();
});

afterEach(function () {
    resetCdkDepictState();
});

function resetCdkDepictState(): void
{
    Closure::bind(function (): void {
        self::$STATUS = false;
        self::$url_parameters = ['host' => ''];
    }, null, CdkDepict::class)();
}

test('cdk depict marks service as connected when health endpoint succeeds', function () {
    config()->set('services.cdk_depict_url', 'https://cdk-depict.test');

    Http::fake([
        'https://cdk-depict.test/test' => Http::response([], 200),
    ]);

    new CdkDepict;

    expect(CdkDepict::is_connected())->toBeTrue();
});

test('cdk depict returns null for empty smiles', function () {
    config()->set('services.cdk_depict_url', 'https://cdk-depict.test');

    Http::fake([
        'https://cdk-depict.test/test' => Http::response([], 200),
    ]);

    $client = new CdkDepict;

    expect($client->get2dStructureUrl(null))->toBeNull();
});

test('cdk depict builds 2d structure url from configured host', function () {
    config()->set('services.cdk_depict_url', 'https://cdk-depict.test/');

    Http::fake([
        'https://cdk-depict.test/test' => Http::response([], 200),
    ]);

    $client = new CdkDepict;

    $url = $client->get2dStructureUrl('CCO', 3.1);
    $query = [];

    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect(parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).parse_url($url, PHP_URL_PATH))
        ->toBe('https://cdk-depict.test/depict/cot/svg')
        ->and($query)->toMatchArray([
            'smi' => 'CCO',
            'abbr' => 'reagents',
            'hdisp' => 'bridgehead',
            'showtitle' => 'true',
            'zoom' => '3.1',
            'annotate' => 'none',
        ]);
});
