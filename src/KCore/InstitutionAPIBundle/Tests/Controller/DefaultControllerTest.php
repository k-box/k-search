<?php

namespace KCore\InstitutionAPIBundle\Tests\Controller;

use JMS\Serializer\Serializer;
use KCore\CoreBundle\Entity\InstitutionDescriptor;
use KCore\CoreBundle\Services\InstitutionService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class DefaultControllerTest.
 *
 * @group solr
 */
class DefaultControllerTest extends WebTestCase
{
    /** @var Serializer $serializer */
    public static $serializer;

    /** @var InstitutionService $institutionService */
    public static $institutionService;

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
        self::$serializer = $container->get('jms_serializer');

        self::$institutionService = $container->get('klink.institution.service');
    }

    protected function tearDown()
    {
        self::$institutionService->deleteAllInstitutions();
        parent::tearDown();
    }


    /**
     * @param string $institutionId
     *
     * @return InstitutionDescriptor
     */
    public function generateInstitutionDescriptor($institutionId)
    {
        $inst = new InstitutionDescriptor($institutionId);
        $inst->setType(str_shuffle('abcdefghijklmnopqrstvwxyz'));
        $inst->setUrl('http://www.url-'.rand().'.org');
        $inst->setName(
            utf8_encode(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- |!"£$%&/()=?^ì*é°§ç:;[]#@§*ç'))
        );
        $inst->setPhone(str_shuffle('1234567890'));
        $inst->setEmail(str_shuffle('abcdefghijklmnopqrstvwxyz.1234567890-').'mail@testmail.com');
        $inst->setThumbnailURI('http://www.url-'.rand().'.org/thumbnail');
        $inst->setCreationDate((new \DateTime())->format('Y-m-d'));

        $inst->setAddressStreet(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- '));
        $inst->setAddressZip(str_shuffle('1234567890'));
        $inst->setAddressLocality(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));
        $inst->setAddressCountry(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));
        $inst->setAddressStreet(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));

        return $inst;
    }

    /**
     * @param InstitutionDescriptor $institution
     * @param string                $username
     * @param string                $password
     *
     * @return null|Response
     */
    protected function doPostInstitution(InstitutionDescriptor $institution, $username, $password)
    {
        $extra = ['CONTENT_TYPE' => 'application/json'];
        if ($username) {
            $extra['PHP_AUTH_USER'] = $username;
        }
        if ($password) {
            $extra['PHP_AUTH_PW'] = $password;
        }

        $client = static::createClient();
        $client->request(
            'POST',
            'institutions/',
            [],
            [],
            $extra,
            self::$serializer->serialize($institution, 'json')
        );

        return $client->getResponse();
    }

    /**
     * @param string $params   Params to append to the "/institutions/" API endpoint
     * @param string $action
     * @param string $username
     * @param string $password
     *
     * @return Response|null
     */
    protected function doRequestInstitution($params, $action, $username, $password)
    {
        $extra = ['CONTENT_TYPE' => 'application/json'];
        if ($username) {
            $extra['PHP_AUTH_USER'] = $username;
        }
        if ($password) {
            $extra['PHP_AUTH_PW'] = $password;
        }

        $client = static::createClient();
        $client->request(
            $action,
            'institutions/'.$params,
            [],
            [],
            $extra
        );

        return $client->getResponse();
    }

    /**
     * @return array
     */
    public function getWrongInstitutionIdSet()
    {
        return [
            ['wrong-institution-id'],
            ['wrong_institution_id'],
            ['wrong*institution*id'],
            ['wrongèinstitutionùid'],
            ['wrong/institution/id'],
            ['wrong:institution:id'],
        ];
    }

    /**
     * @return InstitutionDescriptor[]
     */
    public function generateInstitutionDescriptors($count)
    {
        $items = [];
        foreach (range(1, $count) as $key) {
            $items[] = $this->generateInstitutionDescriptor('testInstitutionId'.$key);
        }

        return $items;
    }

    /**
     * @return InstitutionDescriptor[]
     */
    public function generateWrongInstitutionDescriptors()
    {
        $items = $this->generateInstitutionDescriptors(11);
        foreach ($items as $i => &$item) {
            switch ($i) {
                case 0:
                    $item->setId(null);
                    break;
                case 1:
                    $item->setName(null);
                    break;
                case 2:
                    $item->setType(null);
                    break;
                case 3:
                    $item->setUrl(null);
                    break;
                case 4:
                    $item->setUrl('wrong-uri-definition');
                    break;
                case 5:
                    $item->setThumbnailURI(null);
                    break;
                case 6:
                    $item->setThumbnailURI('wrong-uri-definition');
                    break;
                case 7:
                    $item->setPhone(null);
                    break;
                case 8:
                    $item->setEmail(null);
                    break;
                case 9:
                    $item->setEmail('wrong-email-address');
                    break;
                case 10:
                    $item->setCreationDate(null);
                    break;
                //case 11:
                //    $item->setCreationDate('xxx-xxx-xxx');
                //    break;
                //case 12:
                //    $item->setCreationDate('2014-31-31');
                //    break;
            }
        }

        return [$items];
    }

    /**
     * @param InstitutionDescriptor $institution
     * @dataProvider generateWrongInstitutionDescriptors
     */
    public function testPostWrongInstitution(InstitutionDescriptor $institution)
    {
        $response = $this->doPostInstitution($institution, 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 400, false);
    }

    /**
     * Tests the GET command with wrong ID formats.
     *
     * @dataProvider getWrongInstitutionIdSet
     *
     * @param $institutionId
     */
    public function testGetWrongIDInstitution($institutionId)
    {
        $response = $this->doRequestInstitution($institutionId, 'GET', 'admin@test.org', 'test');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetAllInstitutionsACL()
    {
        $totalInst = 4;
        self::$institutionService->indexInstitutionDescriptors($this->generateInstitutionDescriptors($totalInst));

        $users = [
            ['code' => 200, 'username' => 'admin@test.org', 'password' => 'test'],
            ['code' => 200, 'username' => 'adapter@ext.org', 'password' => 'test'],
            ['code' => 200, 'username' => 'adapter@local.org', 'password' => 'test'],
            ['code' => 200, 'username' => 'dms@ext.org', 'password' => 'test'],
            ['code' => 200, 'username' => 'dms@local.org', 'password' => 'test'],
        ];

        foreach ($users as $user) {
            $response = $this->doRequestInstitution('', 'GET', $user['username'], $user['password']);
            $this->assertJsonResponse($response, $user['code'], false);
            $content = json_decode($response->getContent());
            $this->assertEquals($totalInst, count($content));
        }
    }

    public function provideAllInstitutionData()
    {
        return [
            'empty' => [4, ''],
            '2-results' => [2, '?numResults=2'],
            'all-results-over-10' => [4, '?numResults=10'],
            'all-results-over-invalid' => [4, '?numResults=-10'],

            'all-results-from-0' => [4, '?startResult=0'],
            'all-results-from-1' => [3, '?startResult=1'],
            'all-results-from-4' => [0, '?startResult=4'],
            '2-results-from-1' => [2, '?numResults=2&startResult=1'],
        ];
    }

    /**
     * @dataProvider provideAllInstitutionData
     *
     * @param $count
     * @param $params
     */
    public function testGetAllInstitutions($count, $params)
    {
        $totalInst = 4;
        self::$institutionService->indexInstitutionDescriptors($this->generateInstitutionDescriptors($totalInst));

        $response = $this->doRequestInstitution($params, 'GET', 'admin@test.org', 'test');
        $this->assertJsonResponse($response, 200, false);
        $content = json_decode($response->getContent());
        $this->assertEquals($count, count($content));
    }

    /**
     * Tests the GET command, getting a non existing institution.
     *
     * @depends testGetWrongIDInstitution
     */
    public function testGetInstitution404()
    {
        $response = $this->doRequestInstitution('notexistentid001', 'GET', 'admin@test.org', 'test');
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @param Response $response
     * @param int      $statusCode
     * @param bool     $checkValidJson
     * @param string   $contentType
     */
    protected function assertJsonResponse(Response $response, $statusCode = 200, $checkValidJson = true, $contentType = 'application/json')
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($contentType, $response->headers->get('Content-Type'));

        if ($checkValidJson) {
            $decode = json_decode($response->getContent());
            $this->assertTrue(($decode != null && $decode != false));
        }
    }

    /**
     * @return array
     */
    public function dataProviderPostInstitutionACL()
    {
        return [
            // Admin
            [201, $this->generateInstitutionDescriptor('institutionTestLocal'), 'admin@test.org', 'test'],
            [201, $this->generateInstitutionDescriptor('institutionTestExt'), 'admin@test.org', 'test'],

            // Adapter local
            [201, $this->generateInstitutionDescriptor('institutionTestLocal'), 'adapter@local.org', 'test'],
            [403, $this->generateInstitutionDescriptor('institutionTestExt'), 'adapter@local.org', 'test'],

            // Adapter Ext
            [403, $this->generateInstitutionDescriptor('institutionTestLocal'), 'adapter@ext.org', 'test'],
            [201, $this->generateInstitutionDescriptor('institutionTestExt'), 'adapter@ext.org', 'test'],

            // DMS local
            [201, $this->generateInstitutionDescriptor('institutionTestLocal'), 'dms@local.org', 'test'],
            [403, $this->generateInstitutionDescriptor('institutionTestExt'), 'dms@local.org', 'test'],

            // DMS Ext
            [403, $this->generateInstitutionDescriptor('institutionTestLocal'), 'dms@ext.org', 'test'],
            [201, $this->generateInstitutionDescriptor('institutionTestExt'), 'dms@ext.org', 'test'],
        ];
    }

    /**
     * @param $returnCode
     * @param InstitutionDescriptor $institution
     * @param $username
     * @param $password
     *
     * @dataProvider dataProviderPostInstitutionACL
     */
    public function testPostInstitutionACL($returnCode, $institution, $username, $password)
    {
        $response = $this->doPostInstitution($institution, $username, $password);
        self::$institutionService->deleteInstitutionDescriptor($institution);
        $this->assertEquals($returnCode, $response->getStatusCode());

        if ($returnCode == 201) {
            // Check if Location header is correctly set
            $this->assertEquals('/institutions/'.$institution->getId(), $response->headers->get('Location'));
        }
    }

    /**
     * @return array
     */
    public function dataProviderGetInstitutionACL()
    {
        return [
            // Admin
            [200, $this->generateInstitutionDescriptor('institutionTestLocal'), 'admin@test.org', 'test'],
            [200, $this->generateInstitutionDescriptor('institutionTestExt'), 'admin@test.org', 'test'],

            // Adapter local
            [200, $this->generateInstitutionDescriptor('institutionTestLocal'), 'adapter@local.org', 'test'],
            [200, $this->generateInstitutionDescriptor('institutionTestExt'), 'adapter@local.org', 'test'],

            // Adapter Ext
            [200, $this->generateInstitutionDescriptor('institutionTestLocal'), 'adapter@ext.org', 'test'],
            [200, $this->generateInstitutionDescriptor('institutionTestExt'), 'adapter@ext.org', 'test'],

            // DMS local
            [200, $this->generateInstitutionDescriptor('institutionTestLocal'), 'dms@local.org', 'test'],
            [200, $this->generateInstitutionDescriptor('institutionTestExt'), 'dms@local.org', 'test'],

            // DMS Ext
            [200, $this->generateInstitutionDescriptor('institutionTestLocal'), 'dms@ext.org', 'test'],
            [200, $this->generateInstitutionDescriptor('institutionTestExt'), 'dms@ext.org', 'test'],
        ];
    }

    /**
     * @param $returnCode
     * @param InstitutionDescriptor $institution
     * @param $username
     * @param $password
     *
     * @dataProvider dataProviderGetInstitutionACL
     */
    public function testGetInstitutionACL($returnCode, $institution, $username, $password)
    {
        self::$institutionService->indexInstitutionDescriptor($institution);
        $response = $this->doRequestInstitution($institution->getId(), 'GET', $username, $password);
        self::$institutionService->deleteInstitutionDescriptor($institution);
        $this->assertEquals($returnCode, $response->getStatusCode());

        if ($returnCode == 200) {
            $this->assertJsonResponse($response);
            /* @var InstitutionDescriptor $obj */
            $inst = self::$serializer->deserialize(
                $response->getContent(),
                'KCore\CoreBundle\Entity\InstitutionDescriptor',
                'json'
            );

            $this->assertEquals(InstitutionDescriptor::ENTITY_TYPE, $inst->getEntityType());
            $this->assertEquals(InstitutionDescriptor::ENTITY_TYPE.'_'.$institution->getId(), $inst->getEntityId());
            $this->assertEquals($institution->getName(), $inst->getName());
            $this->assertEquals($institution->getType(), $inst->getType());
            $this->assertEquals($institution->getUrl(), $inst->getUrl());
            $this->assertEquals($institution->getPhone(), $inst->getPhone());
            $this->assertEquals($institution->getEmail(), $inst->getEmail());
            $this->assertEquals($institution->getCreationDate(), $inst->getCreationDate());

            $this->assertEquals($institution->getAddressCountry(), $inst->getAddressCountry());
            $this->assertEquals($institution->getAddressLocality(), $inst->getAddressLocality());
            $this->assertEquals($institution->getAddressZip(), $inst->getAddressZip());
            $this->assertEquals($institution->getAddressStreet(), $inst->getAddressStreet());
        }
    }

    /**
     * @return array
     */
    public function dataProviderDeleteInstitutionACL()
    {
        return [
            // Admin
            [204, $this->generateInstitutionDescriptor('institutionTestLocal'), 'admin@test.org', 'test'],
            [204, $this->generateInstitutionDescriptor('institutionTestExt'), 'admin@test.org', 'test'],

            // Adapter local
            [403, $this->generateInstitutionDescriptor('institutionTestLocal'), 'adapter@local.org', 'test'],
            [403, $this->generateInstitutionDescriptor('institutionTestExt'), 'adapter@local.org', 'test'],

            // Adapter Ext
            [403, $this->generateInstitutionDescriptor('institutionTestLocal'), 'adapter@ext.org', 'test'],
            [403, $this->generateInstitutionDescriptor('institutionTestExt'), 'adapter@ext.org', 'test'],

            // DMS local
            [403, $this->generateInstitutionDescriptor('institutionTestLocal'), 'dms@local.org', 'test'],
            [403, $this->generateInstitutionDescriptor('institutionTestExt'), 'dms@local.org', 'test'],

            // DMS Ext
            [403, $this->generateInstitutionDescriptor('institutionTestLocal'), 'dms@ext.org', 'test'],
            [403, $this->generateInstitutionDescriptor('institutionTestExt'), 'dms@ext.org', 'test'],
        ];
    }

    /**
     * @param $returnCode
     * @param InstitutionDescriptor $institution
     * @param $username
     * @param $password
     *
     * @dataProvider dataProviderDeleteInstitutionACL
     */
    public function testDeleteInstitutionACL($returnCode, $institution, $username, $password)
    {
        self::$institutionService->indexInstitutionDescriptor($institution);
        $response = $this->doRequestInstitution($institution->getId(), 'DELETE', $username, $password);
        self::$institutionService->deleteInstitutionDescriptor($institution);
        $this->assertEquals($returnCode, $response->getStatusCode());
    }
}
