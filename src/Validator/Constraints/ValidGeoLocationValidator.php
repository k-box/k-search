<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidGeoLocationValidator extends ConstraintValidator
{
    private const SUPPORTED_TYPES = [
        'Point',
        'Polygon',
    ];

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

        $data = json_decode($value, true);
        if (null === $data) {
            $error = json_last_error_msg();
            $this->context
                ->buildViolation($constraint->invalidDataMessage)
                ->setParameter('{{ error }}', $error)
                ->setCode(ValidGeoLocation::INVALID_DATA)
                ->addViolation();

            return;
        }

        if (!array_key_exists('type', $data) || !\in_array($data['type'], self::SUPPORTED_TYPES, true)) {
            $this->context
                ->buildViolation($constraint->unsupportedTypeMessage)
                ->setParameter('{{ type }}', $data['type'] ?? 'null')
                ->setCode(ValidGeoLocation::UNSUPPORTED_GEOJSON_TYPE)
                ->addViolation();

            return;
        }

        try {
            $this->ensureValidGeoJSON($data['type'], $data);
        } catch (\InvalidArgumentException $exception) {
            $this->context
                ->buildViolation($constraint->invalidDataMessage)
                ->setParameter('{{ error }}', $exception->getMessage())
                ->setCode(ValidGeoLocation::INVALID_DATA)
                ->addViolation();
        }
    }

    private function ensureValidGeoJSON(string $type, array $data): void
    {
        if (!\is_array($data['coordinates'] ?? null)) {
            throw new \InvalidArgumentException('Invalid "coordinates" property');
        }

        switch ($type) {
            case 'Point':
                // For type "Point", the "coordinates" member is a single position.
                $this->ensureSinglePosition($data['coordinates']);

                break;
            case 'Polygon':
                // Coordinates of a Polygon are an array of linear ring, we do not support holes
                if (1 !== \count($data['coordinates'])) {
                    throw new \InvalidArgumentException('Polygon with holes is not supported');
                }

                if (4 > \count($data['coordinates'][0])) {
                    throw new \InvalidArgumentException('Polygon does not define a closed linear ring');
                }

                foreach ($data['coordinates'][0] as $index => $coordinate) {
                    $this->ensureSinglePosition($coordinate, $index);
                }

                if (reset($data['coordinates'][0]) !== end($data['coordinates'][0])) {
                    throw new \InvalidArgumentException('Polygon does not define a closed linear ring, first and last point MUT be identical');
                }

                break;
        }
    }

    private function ensureSinglePosition(array $data, int $pos = 0): void
    {
        if (2 !== \count($data)) {
            throw new \InvalidArgumentException(sprintf('Coordinates of point #%d are incorrect', $pos));
        }

        if (!\is_numeric($data[0]) || !\is_numeric($data[1])) {
            throw new \InvalidArgumentException(sprintf('Coordinates point #%d are not numeric', $pos));
        }
    }
}
