<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Access;

use Rhino\InputData\InputData;
use Rhino\InputData\MutableInputData;
use Rhinox\JsonApi\Definitions\BoolDefinition;
use Rhinox\JsonApi\Definitions\Definition;
use Rhinox\JsonApi\Definitions\FloatDefinition;
use Rhinox\JsonApi\Definitions\InputDataDefinition;
use Rhinox\JsonApi\Definitions\IntDefinition;
use Rhinox\JsonApi\Definitions\JsonDefinition;
use Rhinox\JsonApi\Definitions\StringDefinition;
use Rhinox\JsonApi\Exception\SerializerException;

class InputDataAccess extends AttributeAccess
{
    /**
     * @param MutableInputData $entity
     */
    public function get(Definition $definition, mixed &$entity): mixed
    {
        $name = $definition->getName();
        switch (true) {
            case $definition instanceof BoolDefinition:
                return $entity->bool($name, null);
            case $definition instanceof FloatDefinition:
                return $entity->decimal($name, null);
            case $definition instanceof InputDataDefinition:
            case $definition instanceof JsonDefinition:
                return $entity->arr($name);
            case $definition instanceof IntDefinition:
                return $entity->int($name, null);
            case $definition instanceof StringDefinition:
                return $entity->string($name, null);
        }
        return $entity->raw($name);
    }

    /**
     * @param MutableInputData $entity
     */
    public function set(Definition $definition, mixed &$entity, mixed $value): void
    {
        $name = $definition->getName();
        $entity->set($name, $value);
    }
}
