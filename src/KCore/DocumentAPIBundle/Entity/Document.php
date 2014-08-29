<?php

namespace KCore\DocumentAPIBundle\Entity;

use KCore\CoreBundle\Entity\DocumentDescriptor;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Exclude;


/**
 * Class Document
 * @package KCore\DocumentAPIBundle\Entity
  */
class Document {

    /**
     * @Type("string")
     * @var String
     * @SerializedName("documentData")
     */
    protected $documentData;

    /**
     * @var DocumentDescriptor
     * @Type("KCore\CoreBundle\Entity\DocumentDescriptor")
     * @SerializedName("descriptor")
     */
    protected $documentDescriptor;

    /**
     * @return DocumentDescriptor
     */
    public function getDocumentDescriptor() {
        return $this->documentDescriptor;
    }

    /**
     * @param DocumentDescriptor $documentDescriptor
     */
    public function setDocumentDescriptor(DocumentDescriptor $documentDescriptor) {
        $this->documentDescriptor = $documentDescriptor;
    }

    /**
     * @return string
     */
    public function getDocumentData() {
        return $this->documentData;
    }

    /**
     * @param string $documentData
     */
    public function setDocumentData($documentData) {
        $this->documentData = $documentData;
    }

} 