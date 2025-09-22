<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Rhinox\JsonApi\Exception\SerializerException;

class StringDefinition
{
    public function __construct(
        private string $name
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue($entity): string
    {
        return $entity->{$this->name};
    }
}
