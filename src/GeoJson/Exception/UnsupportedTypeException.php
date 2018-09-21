<?php

namespace App\GeoJson\Exception;

class UnsupportedTypeException extends \Exception implements GeoJsonException
{
    /**
     * @var string
     */
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;

        parent::__construct(sprintf('Unsupported GeoJson type: "%s"', $type));
    }

    public function getType(): string
    {
        return $this->type;
    }
}
