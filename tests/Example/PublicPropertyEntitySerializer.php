<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

use Rhinox\JsonApi\Access\PropertyAccess;
use Rhinox\JsonApi\Define;
use Rhinox\JsonApi\Serializer;

class PublicPropertyEntitySerializer extends Serializer
{
    protected Define $define {
        get {
            return $this->define ??= new Define(new PropertyAccess());
        }
    }

    public function defineAttributes(): iterable
    {
        yield from $this->define->string('name', required: true);
        yield from $this->define->bool('active', required: true);
    }
}
