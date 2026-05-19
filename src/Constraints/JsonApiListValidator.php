<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class JsonApiListValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof JsonApiList) {
            throw new UnexpectedTypeException($constraint, JsonApiList::class);
        }

        if (is_array($value) && array_is_list($value)) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setParameter('{{ path }}', $constraint->path)
            ->addViolation();
    }
}
