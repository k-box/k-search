<?php

namespace App\Exception\FilterQuery;

class InvalidKlinkFilterException extends FilterQueryException
{
    public static function fromError(string $errorMessage): self
    {
        return new self(sprintf('Invalid K-Links filter: %s', $errorMessage));
    }
}
