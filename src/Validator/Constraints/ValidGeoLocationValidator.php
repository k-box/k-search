<?php

namespace App\Validator\Constraints;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\Exception\InvalidWGS84DataException;
use App\GeoJson\Exception\MalformedJsonDataException;
use App\GeoJson\Exception\UnsupportedTypeException;
use App\GeoJson\Model\Point;
use App\GeoJson\Model\Polygon;
use App\GeoJson\ModelFactory;
use App\GeoJson\WGS84Lib;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidGeoLocationValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidGeoLocation) {
            return;
        }

        if (!$value) {
            return;
        }

        try {
            $model = ModelFactory::buildFromJson($value);
            if ((!$model instanceof Polygon) && (!$model instanceof Point)) {
                throw new UnsupportedTypeException($model::getType());
            }

            // Validate the coordinates to the WGS84 plane
            WGS84Lib::validate($model);
        } catch (MalformedJsonDataException $e) {
            $this->context
                ->buildViolation($constraint->invalidDataMessage)
                ->setParameter('{{ error }}', $e->getMessage())
                ->setCode(ValidGeoLocation::INVALID_DATA)
                ->addViolation();

            return;
        } catch (UnsupportedTypeException $e) {
            $this->context
                ->buildViolation($constraint->unsupportedTypeMessage)
                ->setParameter('{{ type }}', $e->getType())
                ->setCode(ValidGeoLocation::UNSUPPORTED_GEOJSON_TYPE)
                ->addViolation();

            return;
        } catch (InvalidDataException | InvalidWGS84DataException $e) {
            $this->context
                ->buildViolation($constraint->invalidDataMessage)
                ->setParameter('{{ error }}', $e->getMessage())
                ->setCode(ValidGeoLocation::INVALID_DATA)
                ->addViolation();
        }
    }
}
