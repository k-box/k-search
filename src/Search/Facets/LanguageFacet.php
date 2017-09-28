<?php

namespace App\Search\Facets;

use App\Entity\SolrDocumentDescriptor;
use App\Search\Filters\FilterInterface;
use Solarium\QueryType\Select\Query\Component\Facet\Field;

class LanguageFacet extends Field implements FacetInterface
{
    /**
     * @param FilterInterface[] $filters
     */
    public function handleEnabledFilters($filters)
    {
        /*
         * another way to exclude tag/tags
         */
        foreach ($filters as $filter) {
            if ('language' === $filter->getKey()) {
                $this->addExclude('language');
            }
        }
    }

    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_LANGUAGE);
        //$this->addExclude($this->getKey());
    }
}
