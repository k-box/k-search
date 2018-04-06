<?php

namespace App\Exception\FilterQuery;

class InvalidQueryException extends FilterQueryException
{
    public static function fromError(string $errorMessage): self
    {
        return new self(sprintf('Invalid filter query: %s', $errorMessage));
    }
}
