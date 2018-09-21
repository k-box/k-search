<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidGeoLocationFilter extends Constraint
{
    public const INVALID_DATA = '4c4b4996-5a1f-4cf6-8538-2719e0076bb1';
    public const UNSUPPORTED_GEOJSON_TYPE = '25a2b010-2e17-4b89-b5e7-b062e40e4419';

    public $invalidDataMessage = 'Invalid GeoJson data: {{ error }}';
    public $unsupportedTypeMessage = 'Unsupported GeoJson type: {{ type }}';

    protected static $errorNames = [
        self::INVALID_DATA => 'INVALID_DATA_ERROR',
        self::UNSUPPORTED_GEOJSON_TYPE => 'UNSUPPORTED_GEOJSON_TYPE_ERROR',
    ];

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
