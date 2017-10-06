<?php

namespace App\Entity;

use Solarium\QueryType\Update\Query\Document\Document;

interface SolrEntity
{
    /**
     * Returns the entity type for this Solr document.
     *
     * @return string
     */
    public static function getEntityType(): string;

    /**
     * Returns the inner Solr document.
     *
     * @return Document
     */
    public function getSolrUpdateDocument(): Document;

    /**
     * Builds a model from the Solr document.
     *
     * @return mixed
     */
    public function buildModel();

    /**
     * Get the specified field from the underlying Solr document, if exists.
     *
     * @param string $fieldName
     *
     * @return string|array|null
     */
    public function getField(string $fieldName);

    /**
     * Add the given field/value to the underlying Solr document.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function addField(string $key, $value);

    /**
     * Return the fields used to perform a text search, may include boosting.
     *
     * @return string[]
     */
    public static function getTextSearchFields(): array;

    /**
     * Return the fields used to perform aggregations.
     *
     * @return string[]
     */
    public static function getAggregationFields(): array;

    /**
     * Return the fields used to filters.
     *
     * @return string[]
     */
    public static function getFilterFields(): array;

    /**
     * Return a mapping from model properties (json) to the Solr field.
     *
     * @return string[]
     */
    public static function getModelPropertyToFieldMappings(): array;

    /**
     * Return a mapping from Solr fields to their model property (json).
     *
     * @return string[]
     */
    public static function getFieldToModelPropertyMappings(): array;
}
