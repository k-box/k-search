<?php

namespace App\Entity;

use Solarium\QueryType\Update\Query\Document\Document;

class SolrInstitutionDescriptor extends BaseSolrEntity
{
    const FIELD_INST_ID = 'id_institution_id';
    const FIELD_INST_NAME = 'str_ss_institution_name';
    const FIELD_INST_EMAIL = 'str_ss_institution_email';
    const FIELD_INST_PHONE = 'str_ss_institution_phone';

    const FIELD_INST_TYPE = 'str_ss_institution_type';
    const FIELD_INST_URL = 'str_ss_institution_url';
    const FIELD_INST_THUMBNAIL = 'str_ss_institution_thumbnail_uri';

    const FIELD_INST_ADDRESS_LOCALITY = 'str_ss_institution_address_locality';
    const FIELD_INST_ADDRESS_COUNTRY = 'str_ss_institution_address_country';
    const FIELD_INST_ADDRESS_ZIP = 'str_ss_institution_address_zip';
    const FIELD_INST_ADDRESS_STREET = 'str_ss_institution_address_street';
    const FIELD_INST_ADDRESS = 'str_ss_institution_address';

    const FIELD_INST_CREATION_DATE = 'str_ss_institution_creation_date';

    /**
     * @param BaseEntity $institutionDescriptor
     *
     * @return Document|null
     */
    public static function buildFromEntity(BaseEntity $institutionDescriptor)
    {
        $doc = parent::buildFromEntity($institutionDescriptor);
        if ($institutionDescriptor instanceof InstitutionDescriptor) {
            $doc->addField(self::FIELD_INST_ID, $institutionDescriptor->getId());
            $doc->addField(self::FIELD_INST_NAME, $institutionDescriptor->getName());
            $doc->addField(self::FIELD_INST_EMAIL, $institutionDescriptor->getEmail());
            $doc->addField(self::FIELD_INST_PHONE, $institutionDescriptor->getPhone());

            $doc->addField(self::FIELD_INST_TYPE, $institutionDescriptor->getType());
            $doc->addField(self::FIELD_INST_URL, $institutionDescriptor->getUrl());
            $doc->addField(self::FIELD_INST_THUMBNAIL, $institutionDescriptor->getThumbnailURI());

            $doc->addField(self::FIELD_INST_ADDRESS_LOCALITY, $institutionDescriptor->getAddressLocality());
            $doc->addField(self::FIELD_INST_ADDRESS_COUNTRY, $institutionDescriptor->getAddressCountry());
            $doc->addField(self::FIELD_INST_ADDRESS_ZIP, $institutionDescriptor->getAddressZip());
            $doc->addField(self::FIELD_INST_ADDRESS_STREET, $institutionDescriptor->getAddressStreet());
            $doc->addField(self::FIELD_INST_ADDRESS, $institutionDescriptor->getAddress());

            $doc->addField(self::FIELD_INST_CREATION_DATE, $institutionDescriptor->getCreationDate());

            return $doc;
        }
    }

    /**
     * Build the InstitutionDescriptor from the Solr representation.
     *
     * @return InstitutionDescriptor
     */
    public function getInstitutionDescriptor()
    {
        $instDescr = new InstitutionDescriptor($this->getField(self::FIELD_INST_ID));
        $instDescr->setName($this->getField(self::FIELD_INST_NAME));

        $instDescr->setEmail($this->getField(self::FIELD_INST_EMAIL));
        $instDescr->setPhone($this->getField(self::FIELD_INST_PHONE));

        $instDescr->setType($this->getField(self::FIELD_INST_TYPE));
        $instDescr->setUrl($this->getField(self::FIELD_INST_URL));
        $instDescr->setThumbnailURI($this->getField(self::FIELD_INST_THUMBNAIL));

        $instDescr->setAddressLocality($this->getField(self::FIELD_INST_ADDRESS_LOCALITY));
        $instDescr->setAddressCountry($this->getField(self::FIELD_INST_ADDRESS_COUNTRY));
        $instDescr->setAddressZip($this->getField(self::FIELD_INST_ADDRESS_ZIP));
        $instDescr->setAddressStreet($this->getField(self::FIELD_INST_ADDRESS_STREET));
        $instDescr->setAddress($this->getField(self::FIELD_INST_ADDRESS));

        //$date = new \DateTime($this->getField(self::FIELD_INST_CREATION_DATE));
        $instDescr->setCreationDate($this->getField(self::FIELD_INST_CREATION_DATE));

        return $instDescr;
    }
}
