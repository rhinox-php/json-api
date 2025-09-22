<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

class StringDefinition
{
    public function __construct(
        private string $name
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue($entity): string
    {
        return $entity->{$this->name};
    }

    public function setValue($entity, string $value): void
    {
        $entity->{$this->name} = $value;
    }
}
