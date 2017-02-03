<?php

namespace KCore\CoreBundle\Search\Facets;

use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use Solarium\QueryType\Select\Query\Component\Facet\Field;

class DocumentGroupsFacet extends Field implements FacetInterface
{
    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_DOCUMENT_GROUPS);
        $this->addExclude('documentGroups');
    }

    public function handleEnabledFilters($filters)
    {
    }
}
