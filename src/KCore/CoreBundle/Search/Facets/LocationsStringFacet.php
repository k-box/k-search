<?php

namespace KCore\CoreBundle\Search\Facets;


use \Solarium\QueryType\Select\Query\Component\Facet\Field;
use KCore\CoreBundle\Entity\SolrDocumentDescriptor;

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