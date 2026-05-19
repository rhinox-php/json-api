<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

use Rhinox\JsonApi\Serializer;

class TestEntitySerializer extends Serializer
{
    public function defineAttributes(): iterable
    {
        yield from $this->define->string('name', required: true);
    }

    public function defineRelationships(): iterable
    {
        yield from $this->define->single('related', TestRelatedEntitySerializer::class);
    }
}
