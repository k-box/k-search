<?php

namespace KCore\CoreBundle\Services;

use Doctrine\Common\Util\Debug;
use Pnz\GeoJSON\GeoJSONBuilder;
use Pnz\GeoJSON\GeoJSONFeature;

class LocationExtractorService
{
    const ENTRYPOINT_GEOTAG = 'geotag';
    const ENTRYPOINT_GEOTAGMIN = 'geotagmin';

    public static $serverReturnKey = [
        self::ENTRYPOINT_GEOTAG => 'resolvedLocations',
        self::ENTRYPOINT_GEOTAGMIN => 'resolvedLocationsMinimum',
    ];

    protected $serverUrl;

    /**
     * @param $serverJsonKey
     * @param $serverUrl
     */
    public function __construct($serverUrl)
    {
        $this->serverUrl = $serverUrl;
    }

    /**
     * @return string
     */
    public function getServerUrl()
    {
        return $this->serverUrl;
    }

    /**
     * @param $contents
     * @param string $type
     *
     * @return GeoJSONFeature[]|array
     */
    public function extractGeoJSONFeatureFromText($contents, $type = self::ENTRYPOINT_GEOTAGMIN)
    {
        if (empty($this->getServerUrl())) {
            throw new \BadMethodCallException('Missing ServerURL for this invocation!');
        }
        if (!array_key_exists($type, self::$serverReturnKey)) {
            throw new \InvalidArgumentException('Wrong extraction type: "'.$type.'""');
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->post($this->getServerUrl().$type, ['body' => $contents]);
        $json = $response->json();
        // Debug::dump($json[self::$serverReturnKey[$type]], 4);
        if (empty($json) || !is_array($json)) {
            return [];
        }

        $features = [];
        foreach ($json[self::$serverReturnKey[$type]] as $data) {
            $features[] = $this->buildGeoJSONFeaturefromData($data, $type);
        }

        return $features;
    }

    /**
     * @param $data
     * @param string $type
     *
     * @return null|GeoJSONFeature
     */
    protected function buildGeoJSONFeaturefromData($data, $type = self::ENTRYPOINT_GEOTAGMIN)
    {
        if (empty($data) || !is_array($data)) {
            return null;
        }
        $geoJSONGeometry = GeoJSONBuilder::buildGeoJSONGeometryPoint();
        $geoJSONGeometry->addCoordinatePoint($data['longitude'], $data['latitude']);
        $geoJSONFeature = GeoJSONBuilder::buildGeoJSONFeature($geoJSONGeometry);

        switch ($type) {
            case self::ENTRYPOINT_GEOTAG:
                $geoJSONFeature->setProperty('featureClass', $data['featureClass']);
                $geoJSONFeature->setProperty('featureCode', $data['featureCode']);
                $geoJSONFeature->setProperty('primaryCountryName', $data['primaryCountryName']);
            case self::ENTRYPOINT_GEOTAGMIN:
                $geoJSONFeature->setProperty('name', $data['name']);
                $geoJSONFeature->setProperty('geonameID', $data['geonameID']);
                $geoJSONFeature->setProperty('countryCode', $data['countryCode']);
        }

        return $geoJSONFeature;
    }
}
