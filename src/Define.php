<?php

declare(strict_types=1);

namespace Rhinox\JsonApi;

use Rhinox\JsonApi\Definitions\StringDefinition;
use Rhinox\JsonApi\Exception\SerializerException;

class Define
{
    public function string(string $name): \Generator
    {
        yield $name => new StringDefinition($name);
    }
}
