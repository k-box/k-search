<?php

namespace KCore\CoreBundle\Search\Facets;

use \Solarium\QueryType\Select\Query\Component\Facet\Field;
use KCore\CoreBundle\Entity\SolrDocumentDescriptor;

class InstitutionIdFacet extends Field implements FacetInterface
{
    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_INST_ID);
        $this->addExclude($this->getKey());
    }

    public function handleEnabledFilters($filters)
    {

    }
}