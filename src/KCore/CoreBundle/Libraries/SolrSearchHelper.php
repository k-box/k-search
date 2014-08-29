<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 05/11/2014
 * Time: 16:07
 */

namespace KCore\CoreBundle\Libraries;


use Solarium\QueryType\Select\Query\FilterQuery;

class SolrSearchHelper {

    /**
     * @param array $filters
     * @return FilterQuery[]
     */
    public static function buildFilterQueries(array $filters) {
        /** @var FilterQuery[] $queries */
        $queries = array();

        foreach($filters as $key => $filter) {
            if (is_array($filter)) {
                $q = new FilterQuery();
                $q->setKey($key);
                $q->setQuery($filter['field'] . ':' . $filter['value']);

                if (isset($filter['tags'])) {
                    $q->addTags($filter['tags']);
                }
                $queries[] = $q;
            }
        }


        return $queries;
    }

} 