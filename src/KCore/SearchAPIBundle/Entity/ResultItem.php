<?php

namespace KCore\SearchAPIBundle\Entity;

use JMS\Serializer\Annotation\Type;
use KCore\CoreBundle\Entity\DocumentDescriptor;

class ResultItem
{
    /**
     * @var float
     * @Type("float")
     */
    protected $score;

    /**
     * @var DocumentDescriptor
     * @Type("KCore\CoreBundle\Entity\DocumentDescriptor")
     */
    protected $documentDescriptor;

    /**
     * @param float              $score
     * @param DocumentDescriptor $documentDescriptor
     */
    public function __construct($score, DocumentDescriptor $documentDescriptor)
    {
        $this->score = $score;
        $this->documentDescriptor = $documentDescriptor;
    }

    /**
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return DocumentDescriptor
     */
    public function getDocumentDescriptor()
    {
        return $this->documentDescriptor;
    }
}
