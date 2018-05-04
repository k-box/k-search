<?php

namespace App\Exception;

/**
 * Exception thrown when trying to add a Data into the index, when a new version is in the index.
 */
class OutdatedDataRequestException extends KSearchException
{
    public static function fromRequestId(string $requestId): self
    {
        return new self(sprintf(
            'Trying to add Data over a newer version of it (request: %s)',
            $requestId
        ));
    }
}
