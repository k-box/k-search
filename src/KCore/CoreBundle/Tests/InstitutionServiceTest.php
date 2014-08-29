<?php

namespace KCore\CoreBundle\Services;


use KCore\CoreBundle\Entity\InstitutionDescriptor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class InstitutionServiceTest extends WebTestCase {

    /** @var InstitutionService $institutionService */
    static $institutionService;

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
        self::$institutionService = $container->get('klink.institution.service');
    }

    /**
     * Institution dataProvider
     *
     * @param int $total
     * @return array
     */
    public static function generateInstitutionDescriptors($testedMethod, $total = 5) {
        static $institutions = array();
        if (empty($institutions)) {
            foreach (range(1, $total) as $i) {
                $inst = new InstitutionDescriptor('testInstitutionID' . $i);
                $inst->setUrl('http://www.url-' . rand() . '.org');
                $inst->setName(utf8_encode(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- |!"£$%&/()=?^ì*é°§ç:;[]#@§*ç')));
                $inst->setAddressStreet(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890- '));
                $inst->setAddressZip(str_shuffle('1234567890'));
                $inst->setAddressLocality(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));
                $inst->setAddressStreet(str_shuffle('abcdefghijklmnopqrstvwxyz1234567890 '));
                $inst->setPhone(str_shuffle('1234567890'));
                $inst->setEmail(str_shuffle('abcdefghijklmnopqrstvwxyz.1234567890-') . 'mail@testmail.com');
                $institutions[] = array($inst);
            }
        }
       return $institutions;
    }

    /**
     * @dataProvider generateInstitutionDescriptors
     */
    public function testIndexInstitution(InstitutionDescriptor $institution) {
        $response = self::$institutionService->indexInstitutionDescriptor($institution);
        $this->assertEquals(0, $response->getStatus());
    }

    /**
     * @dataProvider generateInstitutionDescriptors
     * @depends testIndexInstitution
     */
    public function testGetInstitition(InstitutionDescriptor $institution) {
        $inst = self::$institutionService->getInstitutionDescriptor($institution->getId());
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

    /**
     * @dataProvider generateInstitutionDescriptors
     * @depends testGetInstitition
     */
    public function testDeleteInstitution(InstitutionDescriptor $institution) {
        $response = self::$institutionService->deleteInstitutionDescriptor($institution);
        $this->assertEquals(0, $response->getStatus());

        $inst = self::$institutionService->getInstitutionDescriptor($institution->getId());
        $this->assertNull($inst);
    }

}
 