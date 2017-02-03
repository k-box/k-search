<?php
/**
 * System request handler for Solarium.
 */

namespace Solarium\QueryType\Collections;

use Solarium\Core\Query\Query as BaseQuery;
use Solarium\QueryType\Collections\Command\Command;

/**
 * System query.
 */
class Query extends BaseQuery
{
    /**
     * Update command add.
     */
    const COMMAND_CLUSTER_STATUS = 'clusterstatus';

    /**
     * Collection command types.
     *
     * @var array
     */
    protected $commandTypes = [
      self::COMMAND_CLUSTER_STATUS => 'Solarium\QueryType\Collections\Query\Command\ClusterStatus',
    ];

    /**
     * Querytype collections.
     */
    const QUERY_COLLECTIONS = 'collections';

    /**
     * Default options for the "Stats.jsp" query type.
     *
     * @var array
     */
    protected $options = [
        'resultclass' => 'Solarium\QueryType\Collections\Result',
        'handler' => 'admin/collections',
    ];

    /**
     * Array of commands.
     *
     * The commands will be executed in the order of this array, this can be
     * important in some cases. For instance a rollback.
     *
     * @var Command
     */
    protected $command = null;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::QUERY_COLLECTIONS;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestBuilder()
    {
        return new RequestBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseParser()
    {
        return new ResponseParser();
    }

    /**
     * Get the command for this query.
     *
     * @return Command
     */
    public function getCommand()
    {
        return $this->command;
    }

    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }
}
