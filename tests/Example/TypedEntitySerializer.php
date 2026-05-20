<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

use Rhinox\JsonApi\Serializer;

class TypedEntitySerializer extends Serializer
{
    public function defineAttributes(): iterable
    {
        yield from $this->define->int('quantity', required: true);
        yield from $this->define->float('price', required: true);
        yield from $this->define->bool('active', required: true);
        yield from $this->define->dateTime('publishedAt', required: true);
    }
}
