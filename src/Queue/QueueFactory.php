<?php

namespace App\Queue;

use Bernard\Queue;

class QueueFactory implements \Bernard\QueueFactory
{
    /**
     * @var \Bernard\QueueFactory
     */
    private $factory;

    /**
     * QueueFactory constructor.
     */
    public function __construct(\Bernard\QueueFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param  string $queueName
     * @return Queue
     */
    public function create($queueName)
    {
        return $this->factory->create($queueName);
    }

    /**
     * @return Queue[]
     */
    public function all()
    {
        return $this->factory->all();
    }

    /**
     * @param  string $queueName
     * @return boolean
     */
    public function exists($queueName)
    {
        return $this->factory->exists($queueName);
    }

    /**
     * @param string $queueName
     */
    public function remove($queueName)
    {
        return $this->factory->remove($queueName);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->factory->count();
    }
}