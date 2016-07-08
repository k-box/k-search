<?php

namespace KCore\CoreBundle\Search\Filters;

use Solarium\QueryType\Select\Query\FilterQuery;
use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use KCore\CoreBundle\Libraries\SolrSearchHelper;

class DocumentHashFilter extends FilterQuery implements FilterInterface
{
    protected $field;

    function init()
    {
        parent::init();
        $this->field = SolrDocumentDescriptor::FIELD_DOC_HASH;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setDefaultQueryWithValue($value){
        $this->setQuery(SolrSearchHelper::buildFilterQueryForSingleValue($this->getField(), $value));
    }
}
