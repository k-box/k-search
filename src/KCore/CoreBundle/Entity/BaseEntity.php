<?php

namespace KCore\CoreBundle\Entity;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;


abstract class BaseEntity {

    const ENTITY_TYPE = 'unknown';

    /**
     * The EntityID (used as a UUID)
     *
     * @var string
     * @Serializer\Exclude
     */
    protected $entityId;

    /**
     * The Object Id
     *
     * @var string
     * @Serializer\Exclude
     */
    protected $id;

    /**
     * @param String $id
     */
    function __construct($id) {
        $this->setId($id);
    }

    /**
     * @return String
     */
    public function getEntityType() {
        return $this::ENTITY_TYPE;
    }


    /**
     * @return String
     */
    public function getEntityId() {
        return $this::ENTITY_TYPE . '_' . $this->getId();
    }

    /**
     * @return String
     *
     * @Assert\NotBlank
     * @Assert\Regex(pattern="/^[a-zA-Z0-9-]+$/")
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param String $id
     */
    public function setId($id) {
        $this->id = $id;
    }

}
