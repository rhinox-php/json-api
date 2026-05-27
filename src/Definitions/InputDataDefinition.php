<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Rhino\InputData\InputData;
use Symfony\Component\Validator\Constraints;

class InputDataDefinition extends Definition
{
    public function parseValue(mixed $value): mixed
    {
        return new InputData($value);
    }

    public function getConstraints(): array
    {
        return [
            ...($this->getRequired() ? [new Constraints\NotBlank()] : []),
            new Constraints\AtLeastOneOf([
                new Constraints\Type('array'),
                new Constraints\Type('object'),
                new Constraints\IsNull(),
            ]),
        ];
    }
}
