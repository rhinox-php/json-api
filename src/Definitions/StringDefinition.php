<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints;

class StringDefinition extends Definition
{
    public function castValue(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function getConstraints(): array
    {
        // @todo validate string type
        return [
            ...($this->getRequired() ? [new Constraints\NotBlank()] : []),
            new Constraints\AtLeastOneOf([
                new Constraints\Type('string'),
                new Constraints\IsNull(),
            ]),
        ];
    }
}
