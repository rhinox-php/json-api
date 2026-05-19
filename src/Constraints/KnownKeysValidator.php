<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class KnownKeysValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof KnownKeys) {
            throw new UnexpectedTypeException($constraint, KnownKeys::class);
        }

        if (!is_array($value)) {
            return;
        }

        foreach (array_keys($value) as $key) {
            if (in_array($key, $constraint->keys, true)) {
                continue;
            }

            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ path }}', $constraint->path)
                ->setParameter('{{ key }}', (string) $key)
                ->addViolation();
        }
    }
}
