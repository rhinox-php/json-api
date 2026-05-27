<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Access;

use Rhinox\JsonApi\Definitions\Definition;
use Rhinox\JsonApi\Exception\SerializerException;

class GetterSetterAccess extends AttributeAccess
{
    public function get(Definition $definition, mixed &$entity): mixed
    {
        $name = $definition->getName();
        $getter = 'get' . ucfirst($name);

        if (!method_exists($entity, $getter)) {
            // @todo use exception context
            throw new SerializerException('Entity must have method "' . $getter . '"');
        }

        return $entity->{$getter}();
    }

    public function set(Definition $definition, mixed &$entity, mixed $value): void
    {
        $name = $definition->getName();
        $setter = 'set' . ucfirst($name);

        if (!method_exists($entity, $setter)) {
            throw new SerializerException(sprintf('Entity must have method "%s"', $setter));
        }

        $entity->{$setter}($value);
    }
}
