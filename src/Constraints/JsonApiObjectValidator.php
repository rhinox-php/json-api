<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class JsonApiObjectValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof JsonApiObject) {
            throw new UnexpectedTypeException($constraint, JsonApiObject::class);
        }

        if ($value === null || (is_array($value) && ($value === [] || !array_is_list($value)))) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setParameter('{{ path }}', $constraint->path)
            ->addViolation();
    }
}
