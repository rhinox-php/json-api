<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints;

class IntDefinition extends Definition
{
    public function castValue(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function getConstraints(): array
    {
        return [
            ...($this->getRequired() ? [new Constraints\NotBlank()] : []),
            new Constraints\AtLeastOneOf([
                new Constraints\Type('integer'),
                new Constraints\Regex('/^-?\d+$/'),
                new Constraints\IsNull(),
            ]),
        ];
    }
}
