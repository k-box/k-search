<?php

namespace App\Model\Search;

use App\Entity\DocumentDescriptor;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

/**
 * Class Document.
 */
class Document
{
    /**
     * The contents of the document, encoded as base64.
     *
     * @var string
     * @Type("string")
     * @SerializedName("documentData")
     */
    protected $documentData;

    /**
     * The DocumentDescriptor, representation of the document metadata.
     *
     * @var DocumentDescriptor
     * @Type("App\Entity\DocumentDescriptor")
     * @SerializedName("descriptor")
     */
    protected $documentDescriptor;

    /**
     * @return DocumentDescriptor
     */
    public function getDocumentDescriptor()
    {
        return $this->documentDescriptor;
    }

    /**
     * @param DocumentDescriptor $documentDescriptor
     */
    public function setDocumentDescriptor(DocumentDescriptor $documentDescriptor)
    {
        $this->documentDescriptor = $documentDescriptor;
    }

    /**
     * @return string
     */
    public function getDocumentData()
    {
        return $this->documentData;
    }

    /**
     * @param string $documentData
     */
    public function setDocumentData($documentData)
    {
        $this->documentData = $documentData;
    }
}
