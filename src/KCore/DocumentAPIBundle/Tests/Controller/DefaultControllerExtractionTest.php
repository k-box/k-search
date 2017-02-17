<?php

namespace KCore\DocumentAPIBundle\Tests\Controller;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\DocumentAPIBundle\Entity\Document;

/**
 * @group solr
 */
class DefaultControllerExtractionTest extends BaseDocumentAPITestClass
{
    /**
     * @return array
     */
    public function locationDataProvider()
    {
        $documentFiles = self::getphysicalDocs();
        $institutionId = 'InstitutionTestA';

        $documents = [];
        foreach ($documentFiles as $type => $documentFile) {
            $documentId = 'TestLocationEntityExtraction'.ucfirst(str_replace(['-','/'], '', $type));
            $document = self::generateDocument(
              $institutionId,
              DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC,
              $documentFile,
              $documentId
            );
            // Remove automatically generated Locations
            $document->getDocumentDescriptor()->clearLocations();

            $locations = ['Biskek', 'Karakol'];
            $documents[] = [$locations, $institutionId.'-'.$documentId, $document];
        }

        return $documents;
    }

    /**
     * @dataProvider locationDataProvider
     *
     * @param $locations
     * @param $document
     * @param $documentId
     */
    public function testLocationEntityExtraction(array $locations, $documentId, Document $document)
    {
        if (empty(self::$locationExtractorService->getServerUrl())) {
            $this->markTestSkipped('Missing ExtractoionServerURL configuration');
        }

        self::$documentService->deleteAllDocumentDescriptors('public');

        $response = $this->doPostDocument($document, 'admin@test.org', 'test');

        $this->assertJsonResponse($response, 201);

        $storedDescriptor = self::$documentService->getDocumentDescriptorById($documentId, 'public');

        $this->assertEquals($locations, $storedDescriptor->getLocationsString());
        $this->assertCount(count($locations), $storedDescriptor->getLocations());
        $this->assertContainsOnlyInstancesOf('Pnz\GeoJSON\GeoJSONFeature', $storedDescriptor->getLocations());
        for ($i = 0; $i < count($locations); ++$i) {
            $cLocation = $storedDescriptor->getLocations()[$i];
            $this->assertEquals($locations[$i], $cLocation->getProperty('name'));
        }
    }
}
