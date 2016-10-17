<?php

namespace KCore\CoreBundle\Tests;

use Doctrine\Common\Util\Debug;
use KCore\CoreBundle\Services\LocationExtractorService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class LocationExtractionServiceTest.
 *
 * @group location-extraction
 */
class LocationExtractionServiceTest extends WebTestCase
{
    /** @var LocationExtractorService */
    public static $locationExtractorService;

    /**
     * Service Loading and Configuration.
     */
    public static function setUpBeforeClass()
    {
        //start the symfony kernel
        /** @var KernelInterface $kernel */
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();

        //now we can instantiate our service (if you want a fresh one for
        //each test method, do this in setUp() instead
        self::$locationExtractorService = $container->get('klink.locationextractor.service');
    }

    public function locationExtractionProvider()
    {
        return [
            // First example: looking for Bishkek and Karakol
            [
                // Expected
                [
                    // Keys are GeonameID
                    '1528675' => ['properties' => ['name' => 'Bishkek', 'countryCode' => 'KG']],
                    '1528121' => ['properties' => ['name' => 'Karakol', 'countryCode' => 'KG']],
                ],
                // Contents
                "Hello, I'm a fake user from Bishkek and Karakol.",
            ],

        ];
    }

    /**
     * Test LocationExtractor feature.
     *
     * @group location-extraction
     * @dataProvider locationExtractionProvider
     */
    public function testLocationExtraction($expected, $contents)
    {
        if (empty(self::$locationExtractorService->getServerUrl())) {
            $this->markTestSkipped('Missing ExtractoionServerURL configuration');
        }

        $locations = self::$locationExtractorService->extractGeoJSONFeatureFromText($contents);
        // Debug::dump($locations, 5);

        $this->assertCount(count($expected), $locations, 'Error counting extracted locations!');
        foreach ($locations as $location) {
            $this->assertInstanceOf('Pnz\GeoJSON\GeoJSONFeature', $location);
            $this->assertArrayHasKey($location->getProperty('geonameID'), $expected);

            $item = &$expected[$location->getProperty('geonameID')];
            foreach ($item['properties'] as $key => $value) {
                $this->assertEquals($item['properties'][$key], $location->getProperty($key));
            }
        }
    }
}
