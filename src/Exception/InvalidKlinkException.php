<?php

namespace App\Exception;

class InvalidKlinkException extends KSearchException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
