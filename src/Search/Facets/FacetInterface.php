<?php

namespace App\Search\Facets;

interface FacetInterface
{
    public function setLimit($limit);

    public function setMinCount($minCount);

    public function setField($field);

    public function addExclude($tag);

    public function handleEnabledFilters($filters);

    public function setPrefix($prefix);
}
