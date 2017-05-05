<?php

namespace App\Entity;

use Solarium\QueryType\Update\Query\Document\Document as SolrDocument;

abstract class BaseSolrEntity extends SolrDocument
{
    const FIELD_ENTITY_TYPE = 'entity_type';
    const FIELD_ENTITY_ID = 'entity_id';

    /**
     * @param $fieldName
     *
     * @return string|array|null
     */
    public function getField($fieldName)
    {
        if (!array_key_exists($fieldName, $this->fields)) {
            return;
        }

        return $this->fields[$fieldName];
    }

    /**
     * @param BaseEntity $entity
     *
     * @return \Solarium\QueryType\Update\Query\Document\Document
     */
    public static function buildFromEntity(BaseEntity $entity)
    {
        $doc = new SolrDocument();
        $doc->addField(self::FIELD_ENTITY_ID, $entity->getEntityId());
        $doc->addField(self::FIELD_ENTITY_TYPE, $entity->getEntityType());

        return $doc;
    }

    /**
     * @param \DateTime $datetime
     *
     * @return string
     */
    public static function convertDateTimeToUTC(\DateTime $datetime)
    {
        return gmdate('Y-m-d\TH:i:s\Z', $datetime->format('U'));
    }
}
