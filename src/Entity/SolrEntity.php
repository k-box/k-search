<?php

namespace App\Entity;

use Solarium\QueryType\Update\Query\Document\Document;

/**
 * Default entity for Documents stored in Solr.
 */
abstract class SolrEntity
{
    protected const FIELD_ENTITY_TYPE = 'entity_type';
    protected const FIELD_ENTITY_ID = 'entity_id';

    private $document;

    public function __construct(string $id)
    {
        $this->document = new Document();
        $this->addField(self::FIELD_ENTITY_ID, $id);
        $this->addField(self::FIELD_ENTITY_TYPE, static::getEntityType());
    }

    public function addField($key, $value)
    {
        $this->document->addField($key, $value);
    }

    abstract public function buildModel();

    abstract public static function getEntityType(): string;

    public function getFieldsMapping(): array
    {
    }

    /**
     * Get an entity field.
     *
     * @param $fieldName
     *
     * @return string|array|null
     */
    public function getField($fieldName)
    {
        if (!array_key_exists($fieldName, $this->document->getFields())) {
            return null;
        }

        return $this->document->__get($fieldName);
    }
}
