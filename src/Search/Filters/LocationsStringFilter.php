<?php

namespace App\Search\Filters;

use App\Entity\SolrDocumentDescriptor;
use App\Libraries\SolrSearchHelper;
use Solarium\QueryType\Select\Query\FilterQuery;

class LocationsStringFilter extends FilterQuery implements FilterInterface
{
    protected $field;

    public function init()
    {
        parent::init();
        $this->field = SolrDocumentDescriptor::FIELD_DOC_LOCATION_STRINGS;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setDefaultQueryWithValue($value)
    {
        $this->setQuery(SolrSearchHelper::buildFilterQueryForMultipleValues($this->getField(), $value));
    }
}
