<?php

namespace KCore\CoreBundle\Search\Facets;

use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use KCore\CoreBundle\Search\Filters\FilterInterface;
use Solarium\QueryType\Select\Query\Component\Facet\Field;

class DocumentProjectIdFacet extends Field implements FacetInterface
{
    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_PROJECT_IDS);
    }

    /**
     * @param FilterInterface[] $filters
     */
    public function handleEnabledFilters($filters)
    {
    }
}
