<?php

namespace KCore\SearchAPIBundle\Tests\Controller;

use JMS\Serializer\Serializer;
use KCore\CoreBundle\Entity\DocumentDescriptor;
use KCore\CoreBundle\Services\DocumentService;
use KCore\SearchAPIBundle\Entity\ResultSet;
use KCore\SearchAPIBundle\Entity\Facet;
use KCore\SearchAPIBundle\Entity\FacetItem;
use Pnz\GeoJSON\GeoJSONBuilder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class FiltersAndFacetsTest.
 *
 * @group solr
 */
class FiltersAndFacetsTest extends WebTestCase
{

    /** @var Serializer $serializer */
    public static $serializer;

    /** @var DocumentService $documentService */
    public static $documentService;

    /** @var DocumentDescriptor[] */
    public static $documentDescriptors;

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
        self::$serializer = $container->get('jms_serializer');

        /** @var DocumentService $documentService */
        self::$documentService = $container->get('klink.document.service');

        //wipe and create
        self::$documentService->deleteAllDocumentDescriptors();
        self::createDocumentDescriptors();
    }

    public static function tearDownAfterClass()
    {
        //don't delete all on teardown, so we can visually debug
        //self::$documentService->deleteAllDocumentDescriptors();
    }

    /**
     * @param  array         $parameters
     * @return Response|null
     */
    protected function doRequestSearch($parameters)
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/search/public/',
            $parameters,
            array(),
            array('CONTENT_TYPE' => 'application/json', 'PHP_AUTH_USER' => 'admin@test.org', 'PHP_AUTH_PW' => 'test')
        );

        return $client->getResponse();
    }

    public static function createBasicDocumentDescriptor()
    {
        static $id = 0;
        $faker = \Faker\Factory::create();

        $id++;

        $docDesc = new DocumentDescriptor('institutionTestLocal', $id);
        $docDesc->setDocumentURI($faker->url.'/document'.$id);
        $docDesc->setThumbnailURI($docDesc->getDocumentURI().'/thumbnail');

        $docDesc->setVisibility('public');

        $docDesc->setCreationDate($faker->dateTimeThisMonth);
        $docDesc->setUserOwner($faker->firstName.' Owner <'.$faker->safeEmail.'>');
        $docDesc->setUserUploader($faker->firstName.' Uploader <'.$faker->safeEmail.'>');

        $docDesc->addAuthor($faker->firstName.' Author0 <'.$faker->safeEmail.'>');
        $docDesc->addAuthor($faker->firstName.' Author1 <'.$faker->companyEmail.'>');

        $docDesc->addTitleAlias('title_alias_0 '.$id);
        $docDesc->addTitleAlias('title_alias_1 '.$id);

        $geoJSONFeature = GeoJSONBuilder::buildGeoJSONFeature(GeoJSONBuilder::buildGeoJSONGeometryPoint());
        $geoJSONFeature
            ->setProperty('name', 'Bishkek')
            ->getGeometry()->addCoordinatePoint(72.6373549,40.3067574);
        $docDesc->addLocation($geoJSONFeature);

        $docDesc->addTopicTerm('test_topic_term_0');
        $docDesc->addTopicTerm('test_topic_term_1');

        $docDesc->addDocumentFolder('/test/doc/folder/folder0/subfolder0');
        $docDesc->addDocumentFolder('/test/doc/folder/folder1/subfolder1');

        $docDesc->setTitle('test_title');
        $docDesc->setContents('test_content');
        $docDesc->setAbstract('test_abstract');

        $docDesc->setDocumentType('Document');
        $docDesc->setMimeType('application/pdf');
        $docDesc->setHash($faker->sha256.$faker->sha256);

        return $docDesc;
    }

    public static function createDocumentDescriptors()
    {
        self::$documentDescriptors = array();

        // 4 it
        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('it');
        $d->setContents('document italian');
        $d->setDocumentType('Document');
        $d->addDocumentGroup('1:1');
        $d->addDocumentGroup('2:1');
        $d->addDocumentGroup('3:1');
        $d->addDocumentGroup('4:1');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('it');
        $d->setContents('document italian');
        $d->setDocumentType('Document');
        $d->addDocumentGroup('2:1');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('it');
        $d->setContents('document italian');
        $d->setDocumentType('Document');
        $d->addDocumentGroup('3:1');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('it');
        $d->setContents('memo italian');
        $d->setDocumentType('Memo');
        $d->addDocumentGroup('4:1');
        self::$documentDescriptors[] = $d;

        // 3 en
        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('en');
        $d->setContents('document english');
        $d->setDocumentType('Document');
        $d->addDocumentGroup('1:2');
        $d->addLocation(GeoJSONBuilder::buildGeoJSONFeature($point)->setProperty('name', 'London'));
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('en');
        $d->setContents('document english');
        $d->setDocumentType('Document');
        $d->addDocumentGroup('2:2');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('en');
        $d->setContents('document english');
        $d->setDocumentType('Document');
        $d->addDocumentGroup('3:2');

        // 3 es
        self::$documentDescriptors[] = $d;
        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('es');
        $d->setContents('document spanish');
        $d->setDocumentType('Document');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('es');
        $d->setContents('memo spanish');
        $d->setDocumentType('Memo');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('es');
        $d->setContents('memo spanish');
        $d->setDocumentType('Memo');
        self::$documentDescriptors[] = $d;

        // 1 ru
        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('ru');
        $d->setContents('memo russian');
        $d->setDocumentType('Memo');
        self::$documentDescriptors[] = $d;

        // 9 misc
        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('ng');
        $d->setContents('memo nigeria');
        $d->setDocumentType('ng');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('ar');
        $d->setContents('argentina');
        $d->setDocumentType('ar');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('zh');
        $d->setContents('chinese');
        $d->setDocumentType('zh');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('el');
        $d->setContents('greek');
        $d->setDocumentType('el');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('hu');
        $d->setContents('hungarian');
        $d->setDocumentType('hu');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('yo');
        $d->setDocumentType('yo');
        $d->setContents('yoruba');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('la');
        $d->setDocumentType('la');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('mn');
        $d->setDocumentType('mn');
        self::$documentDescriptors[] = $d;

        $d = self::createBasicDocumentDescriptor();
        $d->setLanguage('pl');
        $d->setDocumentType('pl');
        self::$documentDescriptors[] = $d;

        // Total: 20
        foreach (self::$documentDescriptors as $descriptor) {
            self::$documentService->indexDocumentDescriptor($descriptor);
        }

        return self::$documentDescriptors;
    }


    /**
     * @return array
     */
    public function filtersParametersProvider()
    {
        $parameters = array();
        $parameters[] = array(4, array('query' => '*', 'filter_language' => 'it'));
        $parameters[] = array(3, array('query' => '*', 'filter_language' => 'en'));
        $parameters[] = array(7, array('query' => '*', 'filter_language' => 'it,en'));
        $parameters[] = array(7, array('query' => '*', 'filter_documentType' => 'Document'));
        $parameters[] = array(3, array('query' => '*', 'filter_language' => 'it', 'filter_documentType' => 'Document'));
        $parameters[] = array(0, array('query' => '*', 'filter_language' => 'it', 'filter_documentType' => 'Document2'));

        foreach(range(1,6) as $id) {
            $parameters[] = array(1, array('query' => '*', 'filter_localDocumentId' => $id));
            $parameters[] = array(1, array('query' => '*', 'filter_documentId' => 'institutionTestLocal-'.$id));
        }

        $parameters[] = array(1, array('query' => '*', 'filter_documentHash' => '4dabb42daf843bb167a38b150ae67851a390732f71d5a389a9b4584714ec530d243a513b0f29b9e1331bbcb06bbfc0423101577a26432eb3dc7f0cc2e88089df'));

        return $parameters;
    }

    /**
     * @dataProvider filtersParametersProvider
     * @param int   $expectedResults
     * @param array $parameters
     */
    public function testFilters($expectedResults, $parameters)
    {
        $response = self::doRequestSearch($parameters);

        /** @var ResultSet $searchResults */
        $searchResults = self::$serializer->deserialize($response->getContent(), 'KCore\SearchAPIBundle\Entity\ResultSet', 'json');
        $this->assertEquals($expectedResults, $searchResults->getItemCount());
    }

    public function facetsParametersProvider()
    {
        $parameters = array();
        $parameters[] = array(
            array('language' => array('it' => 4, 'en' => 3, 'es' => 3)),
            array('query' => '*', 'facets' => 'language', 'facet_language_count' => 20),
        );
        $parameters[] = array(
            array('language' => array('it' => 4, 'en' => 3, 'es' => 3, 'ru' => 1, 'ng' => 1, 'ar' => 1, 'zh' => 1, 'el' => 1, 'hu' => 1, 'yo' => 1, 'la' => 1, 'mn' => 1, 'pl' => 1)),
            array('query' => '*', 'facets' => 'language', 'facet_language_mincount' => 1, 'facet_language_count' => 20),
        );
        $parameters[] = array(
            array('language' => array('it' => 4)),
            array('query' => '*', 'facets' => 'language', 'facet_language_mincount' => 4),
        );
        $parameters[] = array(array('language' => 2),
            array('query' => '*', 'facets' => 'language', 'facet_language_count' => 2),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7, 'Memo' => 4)),
            array('query' => '*', 'facets' => 'language,documentType','facet_documentType_count' => 20),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7, 'Memo' => 4, 'ng' => 1, 'ar' => 1, 'zh' => 1, 'el' => 1, 'hu' => 1, 'yo' => 1, 'la' => 1, 'mn' => 1, 'pl' => 1), 'language' => 3),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'facet_documentType_count' => 20),
        );
        $parameters[] = array(
            array('documentType' => 3),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'facet_documentType_count' => 3),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7, 'Memo' => 4), 'language' => array('it' => 4, 'en' => 3, 'es' => 3)),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_documentType_count' => 5),
        );
        $parameters[] = array(
            array('documentType' => 2, 'language' => 10),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_language_mincount' => 1),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7, 'Memo' => 4), 'language' => array('it' => 4, 'en' => 3, 'es' => 3, 'ru' => 1, 'ng' => 1, 'ar' => 1, 'zh' => 1, 'el' => 1, 'hu' => 1, 'yo' => 1, 'la' => 1, 'mn' => 1, 'pl' => 1)),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_language_mincount' => 1, 'facet_language_count' => 20),
        );
        $parameters[] = array(
            array('documentType' => 11, 'language' => array('it' => 4, 'en' => 3, 'es' => 3)),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'facet_documentType_count' => 20),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 3), 'language' => array('it' => 4)),
            array('query' => 'italian', 'facets' => 'language,documentType', 'facet_documentType_count' => 5),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 3, 'Memo' => 1), 'language' => array('it' => 4)),
            array('query' => 'italian', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'facet_documentType_count' => 5),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7), 'language' => array('it' => 3, 'en' => 3)),
            array('query' => 'document', 'facets' => 'language,documentType', 'facet_documentType_count' => 5),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7), 'language' => array('it' => 3, 'en' => 3, 'es' => 1)),
            array('query' => 'document', 'facets' => 'language,documentType', 'facet_language_mincount' => 1, 'facet_documentType_count' => 5),
        );
        $parameters[] = array(
            array('language' => array('it' => 3, 'en' => 3, 'es' => 1)),
            array('query' => 'document', 'facets' => 'language', 'facet_language_mincount' => 1),
        );
        $parameters[] = array(
            array('documentGroups' => array('3:1' => 1, '3:2' => 1)),
            array('query' => '*', 'facets' => 'documentGroups', 'facet_documentGroups_mincount' => 1, 'facet_documentGroups_prefix' => '3:'),
        );
        $parameters[] = array(
            array('language' => array('en' => 3, 'es' => 3)),
            array('query' => '*', 'facets' => 'language', 'facet_language_prefix' => 'e'),
        );




        return $parameters;
    }

    /**
     * @dataProvider facetsParametersProvider
     * @param array $expectedResults
     * @param array $parameters
     */
    public function testFacets($expectedResults, $parameters)
    {
        $response = self::doRequestSearch($parameters);

        /** @var ResultSet $searchResults */
        $searchResults = self::$serializer->deserialize($response->getContent(), 'KCore\SearchAPIBundle\Entity\ResultSet', 'json');

        $this->checkFacets($searchResults, $expectedResults);
    }

    /**
     * @return array
     */
    public function facetsAndFiltersProvider()
    {
        $parameters = array();
        $parameters[] = array(
            array('language' => array('it' => 4, 'en' => 3)),
            array('query' => '*', 'facets' => 'language', 'filter_language' => 'it,en'),
        );
        $parameters[] = array(
            array('language' => array('it' => 4)),
            array('query' => '*', 'facets' => 'language', 'filter_language' => 'it'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 3, 'Memo' => 1), 'language' => array('it' => 4)),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'filter_language' => 'it'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 6, 'Memo' => 1), 'language' => array('it' => 4, 'en' => 3)),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'filter_language' => 'it,en'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 4, 'Memo' => 3), 'language' => array('it' => 4, 'es' => 3)),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'filter_language' => 'it,es'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7), 'language' => array('it' => 3, 'en' => 3)),
            array('query' => '*', 'facets' => 'language,documentType', 'filter_documentType' => 'Document'),
        );
        $parameters[] = array(
            array('documentType' => array('Memo' => 4), 'language' => array('it' => 1, 'es' => 2, 'ru' => 1)),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_language_mincount' => 1, 'filter_documentType' => 'Memo'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7, 'Memo' => 4), 'language' => array('it' => 4, 'en' => 3, 'es' => 3)),
            array('query' => '*', 'facets' => 'language,documentType', 'filter_documentType' => 'Document,Memo'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 7, 'Memo' => 4), 'language' => array('it' => 4, 'en' => 3, 'es' => 3, 'ru' => 1)),
            array('query' => '*', 'facets' => 'language,documentType', 'facet_language_mincount' => 1, 'filter_documentType' => 'Document,Memo'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 3), 'language' => array('it' => 4)),
            array('query' => 'italian', 'facets' => 'language,documentType', 'filter_language' => 'it'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 3, 'Memo' => 1), 'language' => array('it' => 4)),
            array('query' => 'italian', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'filter_language' => 'it'),
        );
        $parameters[] = array(
            array('documentType' => array('Document' => 1, 'Memo' => 2), 'language' => array('es' => 3)),
            array('query' => 'spanish', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'filter_language' => 'es'),
        );
        $parameters[] = array(
            array('documentType' => 0, 'language' => 0),
            array('query' => 'notfoundkeyword', 'facets' => 'language,documentType', 'facet_documentType_mincount' => 1, 'facet_language_mincount' => 1),
        );
        $parameters[] = array(
            array('documentGroups' => array('3:1' => 1, '3:2' => 1)),
            array('query' => '*', 'facets' => 'documentGroups', 'facet_documentGroups_mincount' => 1, 'filter_documentGroups' => '3:*'),
        );

        // Facet-Prefix test (DocumentGroups)
        $parameters[] = array(
            array('documentGroups' => array('1:1' => 1, '2:1' => 2, '3:1' => 2,'4:1' => 2, '1:2' => 1,'2:2' => 1, '3:2' => 1)),
            array('query' => '*', 'facets' => 'documentGroups', 'facet_documentGroups_mincount' => 1),
        );
        $parameters[] = array(
            array('documentGroups' => array('3:1' => 2, '3:2' => 1)),
            array('query' => '*', 'facets' => 'documentGroups', 'facet_documentGroups_mincount' => 1, 'facet_documentGroups_prefix' => '3:'),
        );
        $parameters[] = array(
            array('documentGroups' => array('3:1' => 2)),
            array('query' => '*', 'facets' => 'documentGroups', 'facet_documentGroups_mincount' => 1, 'facet_documentGroups_prefix' => '3:', 'filter_language' => 'it'),
        );
        /*
        // This test seems to not work, given the filter
        $parameters[] = array(
            array('documentGroups' => array('3:1' => 2, '3:2' => 1)),
            array('query' => '*', 'facets' => 'documentGroups', 'facet_documentGroups_mincount' => 1, 'filter_documentGroups' => '3:'),
        );
        //*/
        $parameters[] = array(
            array('documentGroups' => array('3:1' => 1, '3:2' => 1)),
            array('query' => '*', 'facets' => 'documentGroups', 'facet_documentGroups_mincount' => 1, 'facet_documentGroups_prefix' => '3:'),
        );
        $parameters[] = array(
            array('language' => array('en' => 3, 'es' => 3)),
            array('query' => '*', 'facets' => 'language', 'facet_language_prefix' => 'e'),
        );

        return $parameters;
    }

    /**
     * @dataProvider facetsAndFiltersProvider
     * @param $expectedResults
     * @param $parameters
     */
    public function testFacetsAndFilters($expectedResults, $parameters)
    {
        $response = self::doRequestSearch($parameters);

        /** @var ResultSet $searchResults */
        $searchResults = self::$serializer->deserialize($response->getContent(), 'KCore\SearchAPIBundle\Entity\ResultSet', 'json');

        $this->checkFacets($searchResults, $expectedResults);
    }

    /**
     * @param ResultSet $resultSet
     * @param $expectedFacets
     */
    protected function checkFacets(ResultSet $resultSet, $expectedFacets)
    {
        $facets = $resultSet->getFacets();

        // cycle on every expected facet
        foreach ($expectedFacets as $expFacetName => $expFacetItems) {
            //find the expected facet name in facets
            $found = false;
            if (is_array($facets)) {
                /** @var Facet $facet */
                foreach ($facets as $facet) {
                    if ($facet->getName() === $expFacetName) {
                        $found = true;

                        if (is_array($expFacetItems)) {
                            $this->assertEquals(count($expFacetItems), count($facet->getItems()), 'Error for '.$facet->getName().' Facet items count');

                            //facet found, check items
                            foreach ($expFacetItems as $term => $count) {

                                //find the expected term in current facet
                                $itemFound = false;

                                /** @var FacetItem $facetItem */
                                foreach ($facet->getItems() as $facetItem) {
                                    if ($facetItem->getTerm() == $term) {
                                        $itemFound = true;
                                        $this->assertEquals($count, $facetItem->getCount());
                                        break 1;
                                    }
                                }
                                if (!$itemFound) {
                                    $this->fail('FacetItem with term "'.$term.'" not found!');
                                }
                            }
                        } else {
                            $this->assertEquals($expFacetItems, count($facet->getItems()), 'Error for '.$facet->getName().' Facet items count');
                        }
                        break 1;
                    }
                }
            }
            if (!$found) {
                $this->fail('Facet with name "'.$expFacetName.'" not found!');
            }
        }
    }
}
