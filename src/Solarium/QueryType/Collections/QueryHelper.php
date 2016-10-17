<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 15/05/2015
 * Time: 23:30.
 */
namespace Solarium\QueryType\Collections;

use Solarium\Client;
use Solarium\QueryType\Collections\Command\ClusterStatus;

class QueryHelper
{
    public static function execute(Client $client, Query $query, $endpoint = null)
    {
        $endpoint = clone $client->getEndpoint($endpoint);
        if (!empty($endpoint->getCore())) {
            $endpoint->setCore(null);
        } else {
            $parts = explode('/', $endpoint->getPath());
            array_pop($parts);
            $endpoint->setPath(implode('/', $parts));
        }

        return $client->execute($query, $endpoint);
    }

    /**
     * @param \Solarium\Client $client
     */
    public static function registerCollectionsAPI(Client $client)
    {
        $client->registerQueryType(Query::QUERY_COLLECTIONS, 'Solarium\QueryType\Collections\Query');
    }

    /**
     * @param \Solarium\Client $client
     *
     * @return \Solarium\QueryType\Collections\Query
     */
    public static function getQueryStatusQuery(Client $client)
    {
        if (!array_key_exists(Query::QUERY_COLLECTIONS, $client->getQueryTypes())) {
            self::registerCollectionsAPI($client);
        }

        /** @var Query $query */
        $query = $client->createQuery(Query::QUERY_COLLECTIONS);
        $query->setCommand(new ClusterStatus());

        return $query;
    }
}
