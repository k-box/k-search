<?php

namespace App\HTTP;

use Http\Client\HttpClient as VendorHTTPClient;

interface HTTPClient extends VendorHTTPClient
{
    public function requestFile($uri): string;
}
