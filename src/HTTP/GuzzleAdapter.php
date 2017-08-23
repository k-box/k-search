<?php
namespace App\HTTP;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client as VendorGuzzleAdapter;

class GuzzleAdapter extends VendorGuzzleAdapter implements HTTPClient
{

    /**
     * GuzzleAdapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new GuzzleClient());
    }

    public function requestFile($uri): string
    {
        $request = new Request( 'GET', $uri);

        $response = $this->sendRequest($request);
        return (string) $response->getBody();
    }
}