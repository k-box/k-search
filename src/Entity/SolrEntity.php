<?php

namespace App\Entity;

use Solarium\QueryType\Select\Result\AbstractDocument;
use Solarium\QueryType\Update\Query\Document\Document;

/**
 * Default entity for Documents stored in Solr.
 */
abstract class SolrEntity
{
<<<<<<< 3f7063317f64d17d4937712b8bfe3269b2124d81
    public const FIELD_CONTENTS = 'text_contents';
    public const FIELD_ENTITY_TYPE = 'entity_type';
    public const FIELD_ENTITY_ID = 'entity_id';
=======
    const FIELD_ENTITY_TYPE = 'entity_type';
    const FIELD_ENTITY_ID = 'entity_id';
>>>>>>> First data.search logic in services

    /**
     * @var AbstractDocument
     */
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

    abstract public function buildModel();

    abstract public static function getEntityType(): string;

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

<<<<<<< 3f7063317f64d17d4937712b8bfe3269b2124d81
    /**
     * Returns the entity field containing the indexed full-text contents.
     *
     * @return string
     */
    public function getContentsField(): string
    {
        return static::FIELD_CONTENTS;
    }
=======
>>>>>>> First data.search logic in services
}
