<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints;

class JsonDefinition extends Definition
{
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
