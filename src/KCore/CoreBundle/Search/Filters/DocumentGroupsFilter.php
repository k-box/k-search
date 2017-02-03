<?php

namespace KCore\CoreBundle\Search\Filters;

use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use KCore\CoreBundle\Libraries\SolrSearchHelper;
use Solarium\QueryType\Select\Query\FilterQuery;

class DocumentGroupsFilter extends FilterQuery implements FilterInterface
{
    protected $field;

    public function init()
    {
        parent::init();
        $this->field = SolrDocumentDescriptor::FIELD_DOC_DOCUMENT_GROUPS;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setDefaultQueryWithValue($value)
    {
        $this->setQuery(SolrSearchHelper::buildFilterQueryForMultipleValues($this->getField(), $value));
        $this->addTag('documentGroups');
    }
}
