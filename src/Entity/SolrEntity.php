<?php

namespace App\Entity;

use Solarium\QueryType\Select\Result\AbstractDocument;
use Solarium\QueryType\Update\Query\Document\Document;

/**
 * Default entity for Documents stored in Solr.
 */
abstract class SolrEntity
{
    const FIELD_ENTITY_TYPE = 'entity_type';
    const FIELD_ENTITY_ID = 'entity_id';

    /** @var Document */
    private $document;

    /**
     * @param string                $id       The ID of the Entity
     * @param null|AbstractDocument $document The SolrDocument to build the Entity from
     */
    public function __construct(string $id, ?AbstractDocument $document = null)
    {
        if ($document) {
            $this->document = new Document($document->getFields());
        } else {
            $this->document = new Document();
        }
        $this->addField(self::FIELD_ENTITY_ID, $id);
        $this->addField(self::FIELD_ENTITY_TYPE, static::getEntityType());
    }

    /**
     * Add the given field/value to the underlying Solr document.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function addField(string $key, $value)
    {
        $this->document->addField($key, $value);
    }

    /**
     * Builds a model from the Solr document.
     *
     * @return mixed
     */
    abstract public function buildModel();

    /**
     * Returns the entity type for this Solr document.
     *
     * @return string
     */
    abstract public static function getEntityType(): string;

    abstract public static function getIndexableFields(): array;

    public static function getSearchableFields(): array
    {
        return array_keys(self::getIndexableFields());
    }

    public static function getFilterableFields(): array
    {
        return array_keys(self::getIndexableFields());
    }

    public static function getAggregableFields(): array
    {
        return array_keys(self::getIndexableFields());
    }

    /**
     * Returns the inner Solr document.
     *
     * @return Document
     */
    public function getSolrDocument(): Document
    {
        return $this->document;
    }

    /**
     * Get the specified field from the underlying Solr document, if exists.
     *
     * @param string $fieldName
     *
     * @return string|array|null
     */
    public function getField(string $fieldName)
    {
        if (!array_key_exists($fieldName, $this->document->getFields())) {
            return null;
        }

        return $this->document->__get($fieldName);
    }
}
