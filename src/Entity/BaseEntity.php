<?php

namespace App\Entity;

use JMS\Serializer\Annotation as Serializer;

abstract class BaseEntity
{
    const ENTITY_TYPE = 'unknown';

    /**
     * The EntityID (used as a UUID).
     *
     * @var string
     * @Serializer\Exclude
     */
    protected $entityId;

    /**
     * The Object Id.
     *
     * @var string
     * @Serializer\Exclude
     */
    protected $id;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $this->setId($id);
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return $this::ENTITY_TYPE;
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        return $this::ENTITY_TYPE.'_'.$this->getId();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
