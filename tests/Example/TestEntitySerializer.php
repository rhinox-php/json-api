<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

use Rhinox\JsonApi\SerializableInterface;
use Rhinox\JsonApi\Serializer;

class TestEntitySerializer extends Serializer
{
    public function defineAttributes() {
        yield from $this->define->string('name');
    }
}
