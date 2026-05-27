<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints;

class BoolDefinition extends Definition
{
    public function castValue(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }

    public function getConstraints(): array
    {
        return [
            ...($this->getRequired() ? [new Constraints\NotNull()] : []),
            new Constraints\AtLeastOneOf([
                new Constraints\Type('bool'),
                new Constraints\IsNull(),
            ]),
        ];
    }
}
