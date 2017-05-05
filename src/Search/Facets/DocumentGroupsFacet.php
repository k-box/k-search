<?php

namespace App\Search\Facets;

use App\Entity\SolrDocumentDescriptor;
use Solarium\QueryType\Select\Query\Component\Facet\Field;

class DocumentGroupsFacet extends Field implements FacetInterface
{
    public function handleEnabledFilters($filters)
    {
    }

    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_DOCUMENT_GROUPS);
        $this->addExclude('documentGroups');
    }
}
