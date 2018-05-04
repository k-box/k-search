<?php

namespace App\Queue\Message;

interface MessageInterface
{
    public static function fromJson(array $data);
}
