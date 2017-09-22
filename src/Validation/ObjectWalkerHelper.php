<?php

namespace App\Validation;

use App\Model\Data\Data;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ObjectWalkerHelper
{
    /**
     * @param ExecutionContextInterface $context
     *
     * @return bool
     */
    public static function isAVideoData(ExecutionContextInterface $context): bool
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
