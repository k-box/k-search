<?php

namespace KCore\CoreBundle\Search\Facets;

use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use Solarium\QueryType\Select\Query\Component\Facet\Field;

class LocationsStringFacet extends Field implements FacetInterface
{
    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_LOCATION_STRINGS);
    }
    public function handleEnabledFilters($filters)
    {
    }
}
