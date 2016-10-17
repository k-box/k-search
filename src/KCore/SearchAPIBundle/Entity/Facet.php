<?php

namespace KCore\SearchAPIBundle\Entity;

use JMS\Serializer\Annotation\Type;

class Facet
{
    /**
     * @var string
     * @Type("string")
     */
    protected $name;

    /**
     * The list of retrieved facets.
     *
     * @var FacetItem[]
     * @Type("array<KCore\SearchAPIBundle\Entity\FacetItem>")
     */
    protected $items;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->items = [];
    }

    /**
     * @param FacetItem[] $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }

    /**
     * @param FacetItem $facetItem
     */
    public function addItem(FacetItem $facetItem)
    {
        $this->items[] = $facetItem;
    }

    /**
     * @return float
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return FacetItem[]
     */
    public function getItems()
    {
        return $this->items;
    }
}
