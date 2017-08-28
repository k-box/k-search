<?php

namespace App\Queue\Message;

use Bernard\Message\AbstractMessage;

class UUIDMessage extends AbstractMessage
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $uuid;

    public function __construct($uuid)
    {
        $this->name = $this->uuid = $uuid;
    }

    public function getUUID()
    {
        return $this->uuid;
    }
}
