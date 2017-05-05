<?php

namespace App\Model\Search;

use JMS\Serializer\Annotation\Type;

class FacetItem
{
    /**
     * @var int
     * @Type("integer")
     */
    protected $count;

    /**
     * @var string
     * @Type("string")
     */
    protected $term;

    /**
     * @param int    $count
     * @param string $term
     */
    public function __construct($count, $term)
    {
        $this->count = $count;
        $this->term = $term;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getTerm()
    {
        return $this->term;
    }
}
