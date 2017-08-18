<?php

namespace App\Queue\Message;

use App\Model\Data\Data;
use Bernard\Message\DefaultMessage;

class DataForProcessing extends DefaultMessage
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