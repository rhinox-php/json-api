<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RequiredKeysValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RequiredKeys) {
            throw new UnexpectedTypeException($constraint, RequiredKeys::class);
        }

        if (!is_array($value)) {
            return;
        }

        foreach ($constraint->keys as $key) {
            if (array_key_exists($key, $value)) {
                continue;
            }

            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ kind }}', $constraint->kind)
                ->setParameter('{{ key }}', (string) $key)
                ->addViolation();
        }
    }
}
