<?php

namespace App\Model\Search;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;

class ResultSet
{
    /**
     * @var int
     * @Type("integer")
     * @SerializedName("numFound")
     */
    protected $numFound;

    /**
     * @var string
     * @Type("string")
     */
    protected $query;

    /**
     * @var string
     * @Type("string")
     */
    protected $visibility;

    /**
     * @var int
     * @Type("integer")
     * @SerializedName("numResults")
     */
    protected $numResults;

    /**
     * @var int
     * @Type("integer")
     * @SerializedName("startResult")
     */
    protected $startResult;

    /**
     * @var int
     * @Type("integer")
     * @SerializedName("queryTime")
     */
    protected $queryTime;

    /**
     * @var array
     * @Type("array")
     */
    protected $filters;

    /**
     * @var Facet[]
     * @Type("array<App\Entity\Facet>")
     */
    protected $facets;

    /**
     * The list of retrieved results.
     *
     * @var ResultItem[]
     * @Type("array<App\Entity\ResultItem>")
     */
    protected $items;

    /**
     * Default Constructor.
     *
     * @param string $query
     * @param $visibility
     * @param array $filters
     * @param int   $startResult
     * @param int   $numResults
     */
    public function __construct($query, $visibility, $filters, $startResult, $numResults)
    {
        $this->itemCount = 0;
        $this->items = [];
        $this->query = $query;
        $this->visibility = $visibility;
        $this->filters = $filters;
        $this->facets = [];
        $this->startResult = $startResult;
        $this->numResults = $numResults;
    }

    /**
     * @Type("integer")
     * @SerializedName("itemCount")
     * @VirtualProperty
     */
    public function getItemCount()
    {
        return count($this->items);
    }

    /**
     * @return ResultItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param ResultItem[] $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }

    /**
     * @param ResultItem $docDesc
     */
    public function addItem(ResultItem $docDesc)
    {
        $this->items[] = $docDesc;
    }

    /**
     * @return int
     */
    public function getNumFound()
    {
        return $this->numFound;
    }

    /**
     * @param int $numFound
     */
    public function setNumFound($numFound)
    {
        $this->numFound = $numFound;
    }

    /**
     * @return int
     */
    public function getNumResults()
    {
        return $this->numResults;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getQueryTime()
    {
        return $this->queryTime;
    }

    /**
     * @param int $queryTime
     */
    public function setQueryTime($queryTime)
    {
        $this->queryTime = $queryTime;
    }

    /**
     * @return int
     */
    public function getStartResult()
    {
        return $this->startResult;
    }

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return Facet[]
     */
    public function getFacets()
    {
        return $this->facets;
    }

    /**
     * @param Facet[] $facets
     */
    public function setFacets(array $facets)
    {
        $this->facets = $facets;
    }

    /**
     * @param Facet $facet
     */
    public function addFacet(Facet $facet)
    {
        $this->facets[] = $facet;
    }
}
