<?php

namespace KCore\InstitutionAPIBundle\Tests\Controller;

use JMS\Serializer\Serializer;
use KCore\CoreBundle\Entity\InstitutionDescriptor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class DefaultControllerTest extends WebTestCase
{

    /** @var Serializer $serializer */
    static $serializer;

    /**
     * Service Loading and Configuration
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
    }

    /**
     * Institution dataProvider
     *
     * @param int $total
     * @return InstitutionDescriptor[]
     * @see InstitutionServiceTest::generateInstitutionDescriptors()
     */
    public static function _generateInstitutionDescriptors($total = 5)
    {
        $institutions = array();
        foreach (range(1, $total) as $i) {
            $inst = new InstitutionDescriptor('testInstitution' . $i);
            $inst->setType(str_shuffle('abcdefghijklmnopqrstvwxyz'));
            $inst->setUrl('http://www.url-' . rand() . '.org');
            $inst->setName(
                utf8_encode(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- |!"£$%&/()=?^ì*é°§ç:;[]#@§*ç'))
            );
            $inst->setPhone(str_shuffle('1234567890'));
            $inst->setEmail(str_shuffle('abcdefghijklmnopqrstvwxyz.1234567890-') . 'mail@testmail.com');
            $inst->setThumbnailURI('http://www.url-' . rand() . '.org/thumbnail');
            $inst->setCreationDate((new \DateTime())->format('Y-m-d'));

            $inst->setAddressStreet(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- '));
            $inst->setAddressZip(str_shuffle('1234567890'));
            $inst->setAddressLocality(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));
            $inst->setAddressCountry(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));
            $inst->setAddressStreet(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));

            $institutions[] = array($inst);
        }

        return $institutions;
    }

    /**
     * @param InstitutionDescriptor $institution
     * @return null|Response
     */
    protected function doPostInstitution(InstitutionDescriptor $institution)
    {
        $client = static::createClient();
        $client->request(
            'POST',
            'kcore/institutions/',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            self::$serializer->serialize($institution, 'json')
        );

        return $client->getResponse();
    }

    /**
     * @param $id
     * @param string $action
     * @return Response|null
     */
    protected function doRequestInstitution($id, $action = 'GET')
    {
        $client = static::createClient();
        $client->request(
            $action,
            'kcore/institutions/' . $id,
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json')
        );

        return $client->getResponse();
    }

    /**
     * @return array
     */
    public function getWrongInstitutionIdSet()
    {
        return array(
            array('wrong-institution-id'),
            array('wrong_institution_id'),
            array('wrong*institution*id'),
            array('wrongèinstitutionùid'),
            array('wrong/institution/id'),
            array('wrong:institution:id'),
        );
    }

    /**
     * @return InstitutionDescriptor[]
     */
    public function generateInstitutionDescriptors()
    {
        static $items = array();
        if (empty($items)) {
            $items = $this->_generateInstitutionDescriptors(4, true);
        }

        return $items;
    }

    /**
     * @return InstitutionDescriptor[]
     */
    public function generateWrongInstitutionDescriptors()
    {
        static $items = array();
        if (empty($items)) {
            $items = $this->_generateInstitutionDescriptors(11, true);
            foreach ($items as $i => &$it) {
                $item = current($it);
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
        }

        return $items;
    }


    /**
     * @param InstitutionDescriptor $institution
     *
     * @dataProvider generateInstitutionDescriptors
     */
    public function testPostInstitution(InstitutionDescriptor $institution)
    {
        $response = $this->doPostInstitution($institution);
        $this->assertJsonResponse($response, 201);

        // Check if Location header is correctly set
        $this->assertEquals('/kcore/institutions/' . $institution->getId(), $response->headers->get('Location'));
    }

    /**
     * @param InstitutionDescriptor $institution
     * @dataProvider generateWrongInstitutionDescriptors
     */
    public function testPostWrongInstitution(InstitutionDescriptor $institution)
    {
        $response = $this->doPostInstitution($institution);
        $this->assertJsonResponse($response, 400, false);
    }


    /**
     * Tests the GET command with wrong ID formats
     * @dataProvider getWrongInstitutionIdSet
     * @param $institutionId
     */
    public function testGetWrongIDInstitution($institutionId)
    {
        $response = $this->doRequestInstitution($institutionId);
        $this->assertEquals(404, $response->getStatusCode());
    }


    /**
     * @depends      testPostInstitution
     * @dataProvider generateInstitutionDescriptors
     * @param InstitutionDescriptor $institution
     */
    public function testGetInstitution(InstitutionDescriptor $institution)
    {
        $response = $this->doRequestInstitution($institution->getId());
        $this->assertJsonResponse($response);
        if ($response->getStatusCode() == 200) {
            /** @var InstitutionDescriptor $obj */
            $inst = self::$serializer->deserialize(
                $response->getContent(),
                'KCore\CoreBundle\Entity\InstitutionDescriptor',
                'json'
            );

            $this->assertEquals(InstitutionDescriptor::ENTITY_TYPE, $inst->getEntityType());
            $this->assertEquals(InstitutionDescriptor::ENTITY_TYPE . '_' . $institution->getId(), $inst->getEntityId());
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
     * @depends testGetInstitution
     */
    public function testGetAllInstitutions()
    {
        $institutions = $this->generateInstitutionDescriptors();

        $response = $this->doRequestInstitution('');
        $this->assertJsonResponse($response, 200, false);
        $content = json_decode($response->getContent());
        $this->assertEquals(count($institutions), count($content));
    }

    /**
     * @depends      testGetAllInstitutions
     * @dataProvider generateInstitutionDescriptors
     * @param InstitutionDescriptor $institution
     */
    public function testDeleteInstitution(InstitutionDescriptor $institution)
    {
        $response = $this->doRequestInstitution($institution->getId(), 'DELETE');
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * Tests the GET command, getting a non existing institution
     * @depends testGetWrongIDInstitution
     */
    public function testGetInstitution404()
    {
        $response = $this->doRequestInstitution('notexistentid001');
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @param Response $response
     * @param int $statusCode
     * @param bool $checkValidJson
     * @param string $contentType
     */
    protected function assertJsonResponse(
        Response $response,
        $statusCode = 200,
        $checkValidJson = true,
        $contentType = 'application/json'
    ) {
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($contentType, $response->headers->get('Content-Type'));

        if ($checkValidJson) {
            $decode = json_decode($response->getContent());
            $this->assertTrue(($decode != null && $decode != false));
        }
    }
}
