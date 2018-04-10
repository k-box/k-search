<?php

namespace App\Validator\Constraints;

use App\Entity\SolrEntityData;
use App\Exception\FilterQuery\FilterQueryException;
use App\Service\QueryService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidDataSearchFilterValidator extends ConstraintValidator
{
    private $queryService;

    public function __construct(QueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidDataSearchFilter) {
            return;
        }

        $mapping = SolrEntityData::getFilterFields();
        try {
            $this->queryService->getFilterQuery($value, $mapping);
        } catch (FilterQueryException $exception) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ error }}', $exception->getMessage())
                ->addViolation();
        }
    }
}
