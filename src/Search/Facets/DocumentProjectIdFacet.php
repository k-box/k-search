<?php

namespace App\Search\Facets;

use App\Entity\SolrDocumentDescriptor;
use App\Search\Filters\FilterInterface;
use Solarium\QueryType\Select\Query\Component\Facet\Field;

class DocumentProjectIdFacet extends Field implements FacetInterface
{
    /**
     * @param FilterInterface[] $filters
     */
    public function handleEnabledFilters($filters)
    {
    }

    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_PROJECT_IDS);
    }
}
