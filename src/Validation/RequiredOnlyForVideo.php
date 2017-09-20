<?php

namespace App\Validation;

use App\Model\Data\Data;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RequiredOnlyForVideo
{
    public static function validate($object, ExecutionContextInterface $context, $payload)
    {
        if (self::isAVideoData($context)) {
            //var_dump($object); die();
            if (empty($object)) {
                $context->buildViolation(sprintf('The field %s is required when the data type is "video"', $context->getPropertyPath()))
                    ->atPath($context->getPropertyPath())
                    ->addViolation();
            }
        } else {
            if (!empty($object)) {
                $context->buildViolation(sprintf('The field %s is only available when the data type is "video"', $context->getPropertyPath()))
                    ->atPath($context->getPropertyPath())
                    ->addViolation();
            }
        }
    }

    /**
     * @param ExecutionContextInterface $context
     *
     * @return bool
     */
    private static function isAVideoData(ExecutionContextInterface $context): bool
    {
        $path = $context->getPropertyPath();
        $pathSteps = explode('.', $path);
        $root = $context->getRoot();

        $currentObject = $root;
        foreach ($pathSteps as $step) {
            if ($currentObject instanceof Data) {
                if ($currentObject->type === 'video') {
                    return true;
                }
            }
            $currentObject = $currentObject->$step;
        }

        return false;
    }
}
