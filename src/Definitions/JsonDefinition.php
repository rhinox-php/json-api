<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;
use Rhino\InputData\InputData;

class JsonDefinition
{
    public function __construct(
        private string $name
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue($entity): ?InputData
    {
        return $entity->{$this->name}?->isEmpty() ? null : $entity->{$this->name};
    }

    public function setValue($entity, string $value): void
    {
        $entity->{$this->name} = $value;
    }
}
