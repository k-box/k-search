<?php

namespace App\Search\Filters;

use App\Entity\SolrDocumentDescriptor;
use App\Libraries\SolrSearchHelper;
use Solarium\QueryType\Select\Query\FilterQuery;

class DocumentHashFilter extends FilterQuery implements FilterInterface
{
    protected $field;

    public function init()
    {
        parent::init();
        $this->field = SolrDocumentDescriptor::FIELD_DOC_HASH;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setDefaultQueryWithValue($value)
    {
        $this->setQuery(SolrSearchHelper::buildFilterQueryForSingleValue($this->getField(), $value));
    }
}