<?php

namespace KCore\CoreBundle\Search\Facets;


use \Solarium\QueryType\Select\Query\Component\Facet\Field;
use KCore\CoreBundle\Entity\SolrDocumentDescriptor;

class DocumentTypeFacet extends Field implements FacetInterface
{
    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_DOCTYPE);
        $this->addExclude($this->getKey());
    }

    public function handleEnabledFilters($filters)
    {

    }
}