<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidDataSearchFilter extends Constraint
{
    public $message = 'Invalid filter: {{ error }}';
}
