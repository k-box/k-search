<?php

namespace KCore\CoreBundle\Services;

use KCore\CoreBundle\Entity\InstitutionDescriptor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class InstitutionServiceTest.
 *
 * @group solr
 */
class InstitutionServiceTest extends WebTestCase
{
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
        self::$institutionService = $container->get('klink.institution.service');

        // Delete all the previous indexed Institutions
        self::$institutionService->deleteAllInstitutions();
    }

    public static function tearDownAfterClass()
    {
        // Delete all the previous indexed Institutions
        self::$institutionService->deleteAllInstitutions();
    }

    /**
     * Institution dataProvider.
     *
     * @param int $total
     *
     * @return array
     */
    public static function generateInstitutionDescriptors($testedMethod, $total = 3)
    {
        static $institutions = [];
        if (empty($institutions)) {
            foreach (range(1, $total) as $i) {
                $inst = new InstitutionDescriptor('testInstitutionID'.$i);
                $inst->setUrl('http://www.url-'.rand().'.org');
                $inst->setName(utf8_encode(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- |!"£$%&/()=?^ì*é°§ç:;[]#@§*ç')));
                $inst->setAddress(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- '));
                $inst->setAddressCountry(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));
                $inst->setAddressStreet(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- '));
                $inst->setAddressLocality(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));
                $inst->setAddressZip(str_shuffle('1234567890'));
                $inst->setPhone(str_shuffle('1234567890'));
                $inst->setEmail(str_shuffle('abcdefghijklmnopqrstvwxyz.1234567890-').'mail@testmail.com');
                $institutions[] = [$inst];
            }
        }

        return $institutions;
    }

    /**
     * @dataProvider generateInstitutionDescriptors
     */
    public function testIndexInstitution(InstitutionDescriptor $institution)
    {
        $response = self::$institutionService->indexInstitutionDescriptor($institution);
        $this->assertEquals(0, $response->getStatus());
    }

    /**
     * @dataProvider generateInstitutionDescriptors
     * @depends      testIndexInstitution
     *
     * @param InstitutionDescriptor $institution
     */
    public function testGetInstitution(InstitutionDescriptor $institution)
    {
        $inst = self::$institutionService->getInstitutionDescriptor($institution->getId());
        $this->assertEquals(InstitutionDescriptor::ENTITY_TYPE, $inst->getEntityType());
        $this->assertEquals(InstitutionDescriptor::ENTITY_TYPE.'_'.$institution->getId(), $inst->getEntityId());
        $this->assertEquals($institution->getName(), $inst->getName());
        $this->assertEquals($institution->getType(), $inst->getType());
        $this->assertEquals($institution->getUrl(), $inst->getUrl());
        $this->assertEquals($institution->getPhone(), $inst->getPhone());
        $this->assertEquals($institution->getEmail(), $inst->getEmail());
        $this->assertEquals($institution->getCreationDate(), $inst->getCreationDate());

        $this->assertEquals($institution->getAddress(), $inst->getAddress());
        $this->assertEquals($institution->getAddressCountry(), $inst->getAddressCountry());
        $this->assertEquals($institution->getAddressLocality(), $inst->getAddressLocality());
        $this->assertEquals($institution->getAddressStreet(), $inst->getAddressStreet());
        $this->assertEquals($institution->getAddressZip(), $inst->getAddressZip());
    }

    /**
     * @dataProvider generateInstitutionDescriptors
     * @depends      testGetInstitution
     *
     * @param InstitutionDescriptor $institution
     */
    public function testDeleteInstitution(InstitutionDescriptor $institution)
    {
        $response = self::$institutionService->deleteInstitutionDescriptor($institution);
        $this->assertEquals(0, $response->getStatus());

        $inst = self::$institutionService->getInstitutionDescriptor($institution->getId());
        $this->assertNull($inst);
    }

    /**
     * @depends testDeleteInstitution
     */
    public function testGetAllInstitutions()
    {
        $total = 3;
        $institutions = $this->generateInstitutionDescriptors('', $total);
        // Indexing the Institutions
        foreach ($institutions as $inst) {
            self::$institutionService->indexInstitutionDescriptor(current($inst));
        }

        $instIndexed = self::$institutionService->getAllInstitutionDescriptors();
        $this->assertEquals($total, count($instIndexed));

        // Clean up
        foreach ($institutions as $inst) {
            self::$institutionService->deleteInstitutionDescriptor(current($inst));
        }
    }

    public function testDeleteAll()
    {
        $total = 3;
        $institutions = $this->generateInstitutionDescriptors('', $total);
        // Indexing the Institutions
        foreach ($institutions as $inst) {
            self::$institutionService->indexInstitutionDescriptor(current($inst));
        }

        self::$institutionService->deleteAllInstitutions();
        $inst = self::$institutionService->getAllInstitutionDescriptors();
        $this->assertEquals(0, count($inst));
    }
}
