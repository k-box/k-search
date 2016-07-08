<?php

namespace KCore\CoreBundle\Search\Facets;

use KCore\CoreBundle\Search\Filters\FilterInterface;
use \Solarium\QueryType\Select\Query\Component\Facet\Field;
use KCore\CoreBundle\Entity\SolrDocumentDescriptor;

class LanguageFacet extends Field implements FacetInterface
{
    protected function init()
    {
        parent::init();
        $this->setField(SolrDocumentDescriptor::FIELD_DOC_LANGUAGE);
        //$this->addExclude($this->getKey());
    }

    /**
     * @param FilterInterface[] $filters
     */
    public function handleEnabledFilters($filters)
    {
        /**
         * another way to exclude tag/tags
         */
        foreach($filters as $filter){
            if($filter->getKey() == 'language') {
                $this->addExclude('language');
            }
        }
    }
}