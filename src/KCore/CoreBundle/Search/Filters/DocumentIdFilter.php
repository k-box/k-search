<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 31/03/2015
 * Time: 15:05.
 */
namespace KCore\CoreBundle\Search\Filters;

use KCore\CoreBundle\Entity\SolrDocumentDescriptor;
use KCore\CoreBundle\Libraries\SolrSearchHelper;
use Solarium\QueryType\Select\Query\FilterQuery;

class DocumentIdFilter extends FilterQuery implements FilterInterface
{
    protected $field;

    public function init()
    {
        parent::init();
        $this->field = SolrDocumentDescriptor::FIELD_DOC_ID;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setDefaultQueryWithValue($value)
    {
        $this->setQuery(SolrSearchHelper::buildFilterQueryForMultipleValues($this->getField(), $value));
    }
}
