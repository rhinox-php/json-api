<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

class SingleDefinition
{
    public function __construct(
        private string $name,
        private string $serializerClass,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRelatedEntities($entity): ?object
    {
        return $entity->{$this->name};
    }

    public function isSingle(): bool
    {
        return true;
    }

    public function getSerializerClass(): string
    {
        return $this->serializerClass;
    }
}
