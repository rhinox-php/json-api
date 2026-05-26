<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

class ManyDefinition
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

    public function getRelatedEntities(object $entity): iterable
    {
        $getter = 'get' . ucfirst($this->name);
        return $entity->{$getter}() ?? [];
    }

    public function isSingle(): bool
    {
        return false;
    }

    public function getSerializerClass(): string
    {
        return $this->serializerClass;
    }

    public function getExpectedType(): string
    {
        if (!class_exists($this->serializerClass)) {
            return $this->serializerClass;
        }

        $shortName = (new \ReflectionClass($this->serializerClass))->getShortName();
        return preg_replace('/Serializer$/', '', $shortName) ?? $shortName;
    }
}
