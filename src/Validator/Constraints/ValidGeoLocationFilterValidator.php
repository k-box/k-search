<?php

namespace App\Validator\Constraints;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\Exception\InvalidWGS84DataException;
use App\GeoJson\Exception\MalformedJsonDataException;
use App\GeoJson\Exception\UnsupportedTypeException;
use App\GeoJson\Model\Polygon;
use App\GeoJson\ModelFactory;
use App\GeoJson\WGS84Lib;
use App\Model\Data\Search\GeoLocationFilter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidGeoLocationFilterValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidGeoLocationFilter) {
            return;
        }

        if (!$value instanceof GeoLocationFilter) {
            return;
        }

        try {
            $data = ModelFactory::buildFromJson($value->boundingBox);
            if (!$data instanceof Polygon) {
                $this->context
                    ->buildViolation($constraint->unsupportedTypeMessage)
                    ->setParameter('{{ type }}', $data::getType())
                    ->setCode(ValidGeoLocation::UNSUPPORTED_GEOJSON_TYPE)
                    ->addViolation();

                return;
            }

            // Ensure the polygon is inside the WGS84 coordinates system
            WGS84Lib::validatePolygon($data);
        } catch (MalformedJsonDataException | InvalidWGS84DataException $e) {
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
        } catch (InvalidDataException $e) {
            $this->context
                ->buildViolation($constraint->invalidDataMessage)
                ->setParameter('{{ error }}', $e->getMessage())
                ->setCode(ValidGeoLocation::INVALID_DATA)
                ->addViolation();
        }
    }
}
