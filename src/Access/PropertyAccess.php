<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Access;

use Rhinox\JsonApi\Definitions\Definition;
use Rhinox\JsonApi\Exception\SerializerException;

class PropertyAccess extends AttributeAccess
{
    public function get(Definition $definition, mixed &$entity): mixed
    {
        $name = $definition->getName();
        if (!property_exists($entity, $name) && !isset($entity->{$name})) {
            throw new SerializerException(sprintf('Entity must have property "%s"', $name));
        }

        return $entity->{$name};
    }

    public function set(Definition $definition, mixed &$entity, mixed $value): void
    {
        $name = $definition->getName();
        if (!property_exists($entity, $name) && !isset($entity->{$name})) {
            throw new SerializerException(sprintf('Entity must have property "%s"', $name));
        }

        $entity->{$name} = $value;
    }
}
