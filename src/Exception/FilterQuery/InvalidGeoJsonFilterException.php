<?php

namespace App\Exception\FilterQuery;

class InvalidGeoJsonFilterException extends FilterQueryException
{
    public static function fromError(string $errorMessage): self
    {
        return new self(sprintf('Invalid GeoJson filter: %s', $errorMessage));
    }
}
