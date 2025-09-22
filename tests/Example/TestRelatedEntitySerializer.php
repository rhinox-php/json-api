<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

use Rhinox\JsonApi\SerializableInterface;
use Rhinox\JsonApi\Serializer;

class TestRelatedEntitySerializer extends Serializer
{
    public function defineAttributes(): iterable
    {
        yield from $this->define->string('childName');
    }
}
