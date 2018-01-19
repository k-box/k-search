<?php

namespace App\Entity;

use Solarium\QueryType\Select\Result\Document as ResultDocument;
use Solarium\QueryType\Update\Query\Document\Document as UpdateDocument;

/**
 * Default entity for Documents stored in Solr.
 */
abstract class AbstractSolrEntity implements SolrEntity
{
    const FIELD_ENTITY_TYPE = 'entity_type';
    const FIELD_ENTITY_ID = 'entity_id';

    /**
     * @var UpdateDocument
     */
    private $document;

    /**
     * @param string              $id       The ID of the Entity
     * @param null|ResultDocument $document The SolrDocument to build the Entity from,
     */
    public function __construct(string $id, ?ResultDocument $document = null)
    {
        if ($document) {
            $this->document = new UpdateDocument($document->getFields());
        } else {
            $this->document = new UpdateDocument();
        }
        $this->addField(self::FIELD_ENTITY_ID, $id);
        $this->addField(self::FIELD_ENTITY_TYPE, static::getEntityType());
    }

    final public function addField(string $key, $value)
    {
        $this->document->addField($key, $value);
    }

    final public function getSolrUpdateDocument(): UpdateDocument
    {
        return $this->document;
    }

    final public function getField(string $fieldName)
    {
        if (!array_key_exists($fieldName, $this->document->getFields())) {
            return null;
        }

        return $this->document->__get($fieldName);
    }

    final public static function getFieldToModelPropertyMappings(string $mappingType): array
    {
        return array_flip(static::getModelPropertyToFieldMappings($mappingType));
    }

    public static function getModelPropertyToFieldMappings(string $mappingType): array
    {
        switch ($mappingType) {
            case static::MAPPING_AGGREGATIONS:
                return static::getAggregationFields();
            case static::MAPPING_FILTERS:
                return static::getFilterFields();
            case static::MAPPING_SORTING:
                return static::getSortingFields();
        }

        return [];
    }
}
