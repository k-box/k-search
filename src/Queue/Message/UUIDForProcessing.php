<?php

namespace App\Queue\Message;

use Bernard\Message\AbstractMessage;

class UUIDForProcessing extends AbstractMessage
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

    public function getDataUUID()
    {
        return $this->uuid;
    }
}
