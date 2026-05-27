<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints;

class FloatDefinition extends Definition
{
    public function castValue(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    public function getConstraints(): array
    {
        return [
            ...($this->getRequired() ? [new Constraints\NotBlank()] : []),
            new Constraints\AtLeastOneOf([
                new Constraints\Type('numeric'),
                new Constraints\IsNull(),
            ]),
        ];
    }
}
