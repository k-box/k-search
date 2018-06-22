<?php

namespace App\Exception;

/**
 * Exception thrown when handling items in the queue got stuck.
 */
class ProcessingOverloadedException extends KSearchException
{
}
