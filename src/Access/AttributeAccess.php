<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Access;

use Rhinox\JsonApi\Definitions\Definition;

abstract class AttributeAccess
{
    abstract public function get(Definition $definition, mixed &$entity): mixed;
    abstract public function set(Definition $definition, mixed &$entity, mixed $value): void;
}
