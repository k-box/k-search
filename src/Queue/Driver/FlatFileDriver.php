<?php

namespace App\Queue\Driver;

use Bernard\Driver;

class FlatFileDriver extends Driver\FlatFileDriver
{
    const QUEUE_PATH = '../var/queue/';

    public function __construct(string $rootDir)
    {
        parent::__construct($rootDir.'/'.self::QUEUE_PATH);
    }
}
