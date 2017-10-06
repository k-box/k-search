<?php

namespace App\Validation;

use App\Model\Data\Data;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RequiredOnlyForVideo
{
    public static function validate($object, ExecutionContextInterface $context, $payload)
    {
        if (ObjectWalkerHelper::isAVideoData($context)) {
            if (empty($object)) {
                $context->buildViolation(sprintf('The field %s is required when the data type is "video"', $context->getPropertyPath()))
                    ->atPath($context->getPropertyPath())
                    ->addViolation();
            }
        }
    }
}