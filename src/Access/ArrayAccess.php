<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Access;

use Rhinox\JsonApi\Definitions\Definition;

class ArrayAccess extends AttributeAccess
{
    public function get(Definition $definition, mixed &$entity): mixed
    {
        $name = $definition->getName();
        return $entity[$name] ?? null;
    }

    public function set(Definition $definition, mixed &$entity, mixed $value): void
    {
        $name = $definition->getName();
        $entity[$name] = $value;
    }
}
