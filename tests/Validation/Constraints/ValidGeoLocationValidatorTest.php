<?php

namespace App\Tests\Validation\Constraints;

use App\Validator\Constraints\ValidGeoLocation;
use App\Validator\Constraints\ValidGeoLocationValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidGeoLocationValidatorTest extends ConstraintValidatorTestCase
{
    public function testValidateInvalidJson(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('xxx', $constraint);

        $this->buildViolation('Invalid GeoJson data: {{ error }}')
            ->setParameter('{{ error }}', 'Syntax error')
            ->setCode(ValidGeoLocation::INVALID_DATA)
            ->assertRaised();
    }

    public function testValidateUnsupportedGeoJson(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('{"type": "LineString", "coordinates": [100.0, 0.0] }', $constraint);

        $this->buildViolation('Unsupported GeoJson type: {{ type }}')
            ->setParameter('{{ type }}', 'LineString')
            ->setCode(ValidGeoLocation::UNSUPPORTED_GEOJSON_TYPE)
            ->assertRaised();
    }

    public function testValidateUnsupportedGeoJsonTypeNull(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('{"coordinates": [100.0, 0.0] }', $constraint);

        $this->buildViolation('Unsupported GeoJson type: {{ type }}')
            ->setParameter('{{ type }}', 'null')
            ->setCode(ValidGeoLocation::UNSUPPORTED_GEOJSON_TYPE)
            ->assertRaised();
    }

    public function testValidatePoint(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('{"type": "Point", "coordinates": [100.0, 0.0] }', $constraint);

        $this->assertNoViolation();
    }

    public function testValidatePointInvalidPoints(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('{"type": "Point", "coordinates": [] }', $constraint);

        $this->buildViolation('Invalid GeoJson data: {{ error }}')
            ->setParameter('{{ error }}', 'Coordinates of point #0 are incorrect')
            ->setCode(ValidGeoLocation::INVALID_DATA)
            ->assertRaised();
    }

    public function testValidatePolygon(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('{"type": "Polygon", "coordinates": [[[30, 10], [40, 40], [20, 40], [30, 10]]] }', $constraint);

        $this->assertNoViolation();
    }

    public function testValidatePolygonNotClosed(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('{"type": "Polygon", "coordinates": [[[30, 10], [40, 40], [20, 40], [10, 20]]] }', $constraint);

        $this->buildViolation('Invalid GeoJson data: {{ error }}')
            ->setParameter('{{ error }}', 'Polygon does not define a closed linear ring, first and last point MUT be identical')
            ->setCode(ValidGeoLocation::INVALID_DATA)
            ->assertRaised();
    }

    public function testValidatePolygonInvalidNumberOfPoints(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('{"type": "Polygon", "coordinates": [[[30, 10], [40, 40], [30, 10]]] }', $constraint);

        $this->buildViolation('Invalid GeoJson data: {{ error }}')
            ->setParameter('{{ error }}', 'Polygon does not define a closed linear ring')
            ->setCode(ValidGeoLocation::INVALID_DATA)
            ->assertRaised();
    }

    public function testValidatePolygonWithHoles(): void
    {
        $constraint = $this->buildConstraint();
        $this->validator->validate('{"type": "Polygon", "coordinates": [[[30, 10], [40, 40], [30, 10]], [[10,10], [11, 11]]] }', $constraint);

        $this->buildViolation('Invalid GeoJson data: {{ error }}')
            ->setParameter('{{ error }}', 'Polygon with holes is not supported')
            ->setCode(ValidGeoLocation::INVALID_DATA)
            ->assertRaised();
    }

    protected function buildConstraint(): ValidGeoLocation
    {
        return new ValidGeoLocation();
    }

    protected function createValidator()
    {
        return new ValidGeoLocationValidator();
    }
}
