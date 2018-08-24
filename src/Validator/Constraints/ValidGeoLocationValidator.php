<?php

namespace App\Validator\Constraints;

use App\GeoJson\Exception\InvalidDataException;
use App\GeoJson\Exception\MalformedJsonDataException;
use App\GeoJson\Exception\UnsupportedTypeException;
use App\GeoJson\ModelFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidGeoLocationValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidGeoLocation) {
            return;
        }

        if (!$value) {
            return;
        }

        try {
            ModelFactory::buildFromJson($value);
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
        } catch (InvalidDataException $e) {
            $this->context
                ->buildViolation($constraint->invalidDataMessage)
                ->setParameter('{{ error }}', $e->getMessage())
                ->setCode(ValidGeoLocation::INVALID_DATA)
                ->addViolation();
        }
    }
}
