<?php

namespace KCore\CoreBundle\Tests\Entity;

use KCore\CoreBundle\Entity\InstitutionDescriptor;
use KCore\CoreBundle\Entity\SolrInstitutionDescriptor;

class SolrInstitutionDescriptorTest extends \PHPUnit_Framework_TestCase
{

    public function buildEntityDataProvider()
    {
        $builder = function(
            $name = null,
            $type = null,
            $address = null,
            $addressCountry = null,
            $addressLocality = null,
            $addressStreet = null,
            $addressZip = null
        ) {
            $i = new InstitutionDescriptor(random_int(1, 10) );
            $i->setName($name);
            $i->setType($type);
            $i->setAddressCountry($addressCountry);
            $i->setAddressLocality($addressLocality);
            $i->setAddressStreet($addressStreet);
            $i->setAddressZip($addressZip);
            $i->setAddress($address);
            return $i;
        };
        return [
            [$builder()],
            [$builder('name')],
            [$builder(null, 'type')],
            [$builder(null, null, 'address')],
            [$builder(null, null, null, 'country')],
            [$builder(null, null, null, null, 'locality')],
            [$builder(null, null, null, null, null, 'street')],
            [$builder(null, null, null, null, null, null, 'zip')],
            [$builder('name', 'type')],
            [$builder('name', 'type', 'address')],
            [$builder('name', 'type', 'address', 'country')],
            [$builder('name', 'type', 'address', 'country', 'locality')],
            [$builder('name', 'type', 'address', 'country', 'locality', 'street')],
            [$builder('name', 'type', 'address', 'country', 'locality', 'street', 'zip')],
        ];
    }

    /**
     * @param InstitutionDescriptor $inst
     * @dataProvider buildEntityDataProvider
     */
    public function testBuildFromEntity($inst)
    {
        $res = SolrInstitutionDescriptor::buildFromEntity($inst);
        $resFields = $res->getFields();

        if (!is_null($inst->getName())) {
            $this->assertSame($inst->getName(), $resFields[SolrInstitutionDescriptor::FIELD_INST_NAME]);
        }
        else {
            $this->assertArrayNotHasKey(SolrInstitutionDescriptor::FIELD_INST_NAME, $resFields);
        }

        if (!is_null($inst->getAddressCountry())) {
            $this->assertSame($inst->getAddressCountry(), $resFields[SolrInstitutionDescriptor::FIELD_INST_ADDRESS_COUNTRY]);
        }
        else {
            $this->assertArrayNotHasKey(SolrInstitutionDescriptor::FIELD_INST_ADDRESS_COUNTRY, $resFields);
        }

        if (!is_null($inst->getAddressLocality())) {
            $this->assertSame($inst->getAddressLocality(), $resFields[SolrInstitutionDescriptor::FIELD_INST_ADDRESS_LOCALITY]);
        }
        else {
            $this->assertArrayNotHasKey(SolrInstitutionDescriptor::FIELD_INST_ADDRESS_LOCALITY, $resFields);
        }

        if (!is_null($inst->getAddressZip())) {
            $this->assertSame($inst->getAddressZip(), $resFields[SolrInstitutionDescriptor::FIELD_INST_ADDRESS_ZIP]);
        }
        else {
            $this->assertArrayNotHasKey(SolrInstitutionDescriptor::FIELD_INST_ADDRESS_ZIP, $resFields);
        }
        
        if (!is_null($inst->getAddressStreet())) {
            $this->assertSame($inst->getAddressStreet(), $resFields[SolrInstitutionDescriptor::FIELD_INST_ADDRESS_STREET]);
        }
        else {
            $this->assertArrayNotHasKey(SolrInstitutionDescriptor::FIELD_INST_ADDRESS_STREET, $resFields);
        }

        if (!is_null($inst->getAddress())) {
            $this->assertSame($inst->getAddress(), $resFields[SolrInstitutionDescriptor::FIELD_INST_ADDRESS]);
        }
        else {
            $this->assertArrayNotHasKey(SolrInstitutionDescriptor::FIELD_INST_ADDRESS, $resFields);
        }
    }
}
